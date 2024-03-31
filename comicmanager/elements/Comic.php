<?php


namespace datagutten\comicmanager\elements;

use Cake\Database;
use datagutten\comicmanager\exceptions;
use datagutten\comicmanager\exceptions\comicManagerException;
use datagutten\comicmanager\Queries;
use PDO;

class Comic extends DatabaseObject
{
    /**
     * @var array All valid database fields for the comic
     */
    public array $fields;
    /**
     * @var array All possible key fields
     */
    public static array $key_fields = ['id' => 'ID (printed on strip)', 'customid' => 'Custom grouping id', 'original_date' => 'Original published date', 'uid'=>'Unique release id'];
    /**
     * @var string Default key field
     */
    public string $key_field;
    /**
     * @var array Possible key fields
     */
    public array $possible_key_fields;
    /**
     * @var bool Has categories
     */
    public bool $has_categories;
    /**
     * @var string ComicMetadata ID
     */
    public string $id;
    /**
     * @var string ComicMetadata name
     */
    public string $name;

    protected Queries\ComicMetadata $queries_metadata;
    /**
     * @var Queries\Comic
     */
    private Queries\Comic $queries;

    public function __construct($db_config, array $values)
    {
        parent::__construct($values);
        $this->queries_metadata = new Queries\ComicMetadata($db_config);
        $this->queries = new Queries\Comic($db_config);
    }

    /**
     * Load comic metadata from the database
     * @throws exceptions\ComicNotFound Metadata not found
     * @throws exceptions\DatabaseException
     */
    public function load_db(): void
    {
        $st = $this->queries_metadata->info($this);
        if ($st->rowCount() === 0)
            throw new exceptions\ComicNotFound('Metadata not found, invalid comic id?');

        $values = $st->fetch('assoc');

        $this->id = $values['id'];
        $this->name = $values['name'];
        $this->key_field = $values['keyfield'] ?? $values['key_field'];
        $this->has_categories = $values['has_categories'] == 1;
        $this->possible_key_fields = self::parsePossibleKeyFields($values['possible_key_fields']);
        $this->fields = $this->queries_metadata->fields($this);
    }

    /**
     * Save changes in comic metadata to the database
     * @return Database\StatementInterface
     * @throws exceptions\ComicInvalidArgumentException
     * @throws exceptions\DatabaseException
     */
    public function save(): Database\StatementInterface
    {
        $this->allowedKeyField($this->key_field);
        return $this->queries_metadata->update($this);
    }

    /**
     * Create a new comic
     * @throws exceptions\DatabaseException
     * @throws exceptions\comicManagerException
     */
    public function create()
    {
        if(!$this->queries->tableExists('comic_info'))
            $this->queries_metadata->createMetadataTable();

        //Add metadata
        $this->queries_metadata->insert($this);
        //Create table
        $this->queries->createTable($this);
        //Add key field columns
        foreach ($this->possible_key_fields as $key_field)
        {
            $this->queries_metadata->addKeyField($this, $key_field);
        }
        if($this->has_categories)
            $this->queries->enableCategories($this);
        $this->load_db();
    }

    /**
     * Add a new key field for comic
     * Alter metadata and add column to comic table if needed
     * @param $key_field
     * @throws comicManagerException
     */
    public function addKeyField($key_field)
    {
        self::validKeyField($key_field); //Check if the key field is valid
        $this->queries_metadata->addKeyField($this, $key_field);
        $this->possible_key_fields[] = $key_field;
        $this->save();
    }

    /**
     * Find sites for a comic
     * @return array Site slugs
     * @throws exceptions\DatabaseException Database error
     */
    public function sites(): array
    {
        $st = $this->queries->sites($this);
        return $st->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get categories for the comic
     * @param bool $only_visible Show only categories marked as visible
     * @param bool $return_object Return statement object
     * @return array|Database\StatementInterface
     * @throws exceptions\ComicInvalidArgumentException Comic does not have categories
     * @throws exceptions\DatabaseException Database error
     */
    public function categories($only_visible=false, $return_object=false)
    {
        if(!$this->has_categories)
            throw new exceptions\ComicInvalidArgumentException('Comic does not have categories');

        return $this->queries_metadata->categories($this, $only_visible, $return_object);
    }

    /**
     * Get the name of a category
     * @param int $category Category id
     * @return string Category name
     * @throws exceptions\ComicInvalidArgumentException Comic does not have categories
     * @throws exceptions\DatabaseException Database error
     */
    public function categoryName(int $category): string
    {
        $categories = $this->categories();
        return $categories[$category];
    }

    /**
     * Enable categories for the comic
     * @return Database\StatementInterface
     * @throws exceptions\ComicInvalidArgumentException Comic already has categories
     * @throws exceptions\DatabaseException Database error
     */
    function enableCategories(): Database\StatementInterface
    {
        if($this->has_categories)
            throw new exceptions\ComicInvalidArgumentException('Comic already has categories');
        $this->has_categories = true;
        $this->save();
        return $this->queries->enableCategories($this);
    }

    /**
     * Add a category to the comic
     * @param string $name Category name
     * @param bool $visible Should the category be visible?
     * @return Database\StatementInterface
     * @throws exceptions\ComicInvalidArgumentException Comic does not have categories
     * @throws exceptions\DatabaseException Database error
     */
    public function addCategory(string $name, bool$visible = true): Database\StatementInterface
    {
        if (!$this->has_categories)
            throw new exceptions\ComicInvalidArgumentException('Comic does not have categories');
        return $this->queries->addCategory($this, $name, $visible);
    }

    /**
     * Parse a comma separated string with possible key fields
     * @param string $fields Comma separated string with field names
     * @return array Fields
     */
    public static function parsePossibleKeyFields($fields)
    {
        if (strpos($fields, ',') !== false)
            return explode(',', $fields);
        else
            return [$fields];
    }

    /**
     * Build a comma separated string with possible key fields
     * @param array $fields
     * @return string Comma separated string with field names
     */
    public static function buildPossibleKeyFields($fields)
    {
        $fields = array_unique($fields);
        return implode(',', $fields);
    }

    /**
     * @param string $key_fields_string Comma separated string with field names
     * @param string $field Field name to append
     * @return string Comma separated string with field names
     */
    public static function appendKeyField($key_fields_string, $field)
    {
        $fields = self::parsePossibleKeyFields($key_fields_string);
        $fields[] = $field;
        return self::buildPossibleKeyFields($fields);
    }

    /**
     * Check if a field is allowed for the current comic and return its description text
     * @param string $key_field Field to be validate
     * @return string Field description
     * @throws exceptions\ComicInvalidArgumentException Field is not valid
     */
    public function allowedKeyField(string $key_field)
    {
        if(array_search($key_field, $this->possible_key_fields) !== false)
            return self::$key_fields[$key_field];
        else
            throw new exceptions\ComicInvalidArgumentException(sprintf('%s is not a valid key field for %s', $key_field, $this->id));
    }

    /**
     * Check if a field is valid and return its description text
     * @param string $key_field Field to be validate
     * @return string Field description
     * @throws exceptions\ComicInvalidArgumentException Field is not valid
     */
    public static function validKeyField(string $key_field)
    {
        if(!isset(self::$key_fields[$key_field]))
            throw new exceptions\ComicInvalidArgumentException('Invalid key field: '.$key_field);
        else
            return self::$key_fields[$key_field];
    }

    /**
     * Change key field
     * @param string $key_field
     * @throws exceptions\ComicInvalidArgumentException Field is not valid
     */
    public function setKeyField(string $key_field)
    {
        $this->allowedKeyField($key_field);
        $this->key_field = $key_field;
    }

    public function offsetGet($offset)
    {
        if($offset==='keyfield')
            return $this->key_field;
        return $this->$offset;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @throws exceptions\ComicInvalidArgumentException
     */
    public function offsetSet($offset, $value)
    {
        if($offset==='key_field' && !isset(self::$key_fields[$value]))
            throw new exceptions\ComicInvalidArgumentException('Invalid key field: '.$value);
        if($offset==='id' && !preg_match('/^[a-z_]+$/',$value))
            throw new exceptions\ComicInvalidArgumentException('Invalid comic id: '.$value);

        $this->$offset = $value;
    }
}