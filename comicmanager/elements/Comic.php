<?php


namespace datagutten\comicmanager\elements;


use ArrayAccess;
use datagutten\comicmanager\exceptions\comicManagerException;
use InvalidArgumentException;
use PDO;

class Comic implements ArrayAccess
{
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
     * @var string Comic ID
     */
    public string $id;
    /**
     * @var string Comic name
     */
    public string $name;

    public function __construct(array $values)
    {
        foreach ($values as $key => $value)
        {
            $this[$key] = $value;
        }
    }

    /**
     * Load comic information from database
     * @param PDO $db
     * @param string $id
     * @return static
     * @throws comicManagerException
     */
    public static function from_db(PDO $db, string $id): Comic
    {
        $st = $db->prepare('SELECT * FROM comic_info WHERE id=?');
        $st->execute([$id]);
        $values = $st->fetch(PDO::FETCH_ASSOC);
        if($values===false)
            throw new comicManagerException('Comic not found');

        return new static([
            'id' => $values['id'],
            'name' => $values['name'],
            'key_field' => $values['keyfield'],
            'has_categories' => $values['has_categories'] == 1,
            'possible_key_fields' => self::parsePossibleKeyFields($values['possible_key_fields']),
        ]);
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
     * @throws InvalidArgumentException Field is not valid
     */
    public function allowedKeyField(string $key_field)
    {
        if(array_search($key_field, $this->possible_key_fields) !== false)
            return self::$key_fields[$key_field];
        else
            throw new InvalidArgumentException(sprintf('%s is not a valid key field for %s', $key_field, $this->id));
    }

    /**
     * Check if a field is valid and return its description text
     * @param string $key_field Field to be validate
     * @return string Field description
     * @throws InvalidArgumentException Field is not valid
     */
    public static function validKeyField(string $key_field)
    {
        if(!isset(self::$key_fields[$key_field]))
            throw new InvalidArgumentException('Invalid key field: '.$key_field);
        else
            return self::$key_fields[$key_field];
    }

    /**
     * Change key field
     * @param string $key_field
     * @throws InvalidArgumentException Field is not valid
     */
    public function setKeyField(string $key_field)
    {
        $this->allowedKeyField($key_field);
        $this->key_field = $key_field;
    }

    public function offsetExists($offset)
    {
        return !empty($this->$offset);
    }

    public function offsetGet($offset)
    {
        if($offset==='keyfield')
            return $this->key_field;
        return $this->$offset;
    }

    public function offsetSet($offset, $value)
    {
        if($offset==='key_field' && !isset(self::$key_fields[$value]))
            throw new InvalidArgumentException('Invalid key field: '.$value);
        if($offset==='id' && !preg_match('/^[a-z_]+$/',$value))
            throw new InvalidArgumentException('Invalid comic id: '.$value);

        $this->$offset = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }
}