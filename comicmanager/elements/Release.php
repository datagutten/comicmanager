<?php


namespace datagutten\comicmanager\elements;


use Cake\Database;
use datagutten\comicmanager\comicmanager;
use datagutten\comicmanager\exceptions;
use datagutten\comicmanager\exceptions\comicManagerException;
use datagutten\comicmanager\exceptions\ImageNotFound;
use datagutten\comicmanager\Queries;
use DateTime;
use Exception;

class Release extends DatabaseObject
{
    /**
     * @var ?string Release date
     */
    public ?string $date;
    /**
     * @var string Site id
     */
    public string $site;
    /**
     * @var int Release unique id
     */
    public int $uid;
    /**
     * @var ?string Release custom id
     */
    public ?string $customid;
    /**
     * @var ?string Release printed id
     */
    public ?string $id;
    /**
     * @var ?string Original publication date
     */
    public ?string $original_date;
    /**
     * @var ?int Release category id
     */
    public ?int $category;
    /**
     * @var ?string Image file path
     */
    public ?string $image_file;
    /**
     * @var ?string Image URL
     */
    public ?string $image_url;
    /**
     * @var ?string Release title
     */
    public ?string $title;

    /**
     * @var ?ImageNotFound
     */
    public ?ImageNotFound $image_error = null;
    /**
     * @var Comic
     */
    public Comic $comic;
    /**
     * @var Image|null
     */
    public ?Image $image;
    /**
     * @var DateTime Release date
     */
    public DateTime $date_obj;
    /**
     * @var Queries\Release
     */
    private Queries\Release $queries;

    /**
     * Release constructor.
     * @param comicmanager $comicmanager
     * @param array $fields
     * @param bool $load_image
     * @throws comicManagerException
     */
    function __construct(comicmanager $comicmanager, array $fields, $load_image = true)
    {
        $this->comic = $comicmanager->info;
        parent::__construct($fields);
        if (isset($this->date) && !isset($this->date_obj) && !empty($this->date))
            $this->date_obj = self::parse_date($this->date);
        elseif (!isset($this->date) && isset($this->date_obj))
            $this->date = $this->date_obj->format('Ymd');

        $this->queries = new Queries\Release($comicmanager->config['db']);
        if ($load_image)
            $this->image = $this->get_image($comicmanager);
    }

    /**
     * Get image for the release
     * @param comicmanager $comicmanager
     * @return ?Image
     * @throws exceptions\comicManagerException
     */
    function get_image(comicmanager $comicmanager): ?Image
    {
        try
        {
            if(!empty($this->image_url))
                return Image::from_url($this->image_url);
            if(!empty($this->image_file))
                return Image::from_file($this->image_file, $comicmanager);
            list($key_field, $key) = $this->find_key();
            if(!empty($this->site) && !empty($this->date))
                return Image::from_date($this->site, $this->date, $comicmanager);
            elseif(!empty($key))
            {
                return Image::from_key(
                    $this->site,
                    $key,
                    $key_field,
                    $comicmanager);
            }
            else
                throw new ImageNotFound('No valid keys');
        }
        catch (ImageNotFound $e)
        {
            $this->image_error = $e;
            return null;
        }
    }

    /**
     * Find the key and key field
     * @return array[key field, key]
     */
    function find_key(): array
    {
        $fields = $this->comic->possible_key_fields;
        foreach($fields as $key_field)
        {
            if(property_exists($this, $key_field) && !empty($this->$key_field))
                return [$key_field, $this->$key_field];
        }
        return [null, null];
    }

    /**
     * Is the grouping key set for the release?
     * @return bool
     */
    function has_key(): bool
    {
        $key_field = $this->comic->key_field;
        return property_exists($this, $key_field) && !empty($this->$key_field);
    }

    /**
     * Get the grouping key for the release
     * @return ?string
     */
    function key(): ?string
    {
        $key_field = $this->comic->key_field;
        if ($this->has_key())
            return $this->$key_field;
        else
            return null;
    }

    /**
     * Save the release to the database
     * @param bool $allow_insert Allow inserting new rows
     * @return ?Database\StatementInterface
     * @throws exceptions\comicManagerException
     * @throws exceptions\DatabaseException
     */
    public function save($allow_insert = true): ?Database\StatementInterface
    {
        if (empty($this->uid))
        {
            try
            {
                $uid = $this->queries->get_uid($this);
            }
            catch (exceptions\ComicInvalidArgumentException $e)
            {
                if (!$allow_insert)
                    throw $e;
                else
                    $uid = null;
            }
            if ($uid === null)
            {
                if (!$allow_insert)
                    throw new comicManagerException('UID not found, but insert is not allowed');
                $st = $this->queries->insert($this);
                $this->uid = $st->lastInsertId();
                return $st;
            }
            else
                $this->uid = $uid;
        }

        return $this->queries->update($this);
    }

    /**
     * @throws exceptions\ReleaseNotFound|comicManagerException
     */
    public function load_db(): void
    {
        $st = $this->queries->get($this);
        if ($st->rowCount() > 1)
        {
            $field_string = '';
            foreach ($this->comic->fields as $key)
            {
                if (isset($this->$key))
                    $field_string .= sprintf('%s=%s ', $key, $this->$key);
            }
            throw new exceptions\comicManagerException('Multiple releases found for ' . $field_string);
        }
        else
            $info = $st->fetch('assoc');

        if (empty($info))
            throw new exceptions\ReleaseNotFound($this);
        foreach ($info as $key => $value)
        {
            $this->$key = $value;
        }
    }

    /**
     * Parse a string date to a DateTime object
     * @param string $date
     * @return DateTime
     * @throws comicManagerException
     */
    public static function parse_date(string $date): DateTime
    {
        try
        {
            return new DateTime($date);
        }
        catch (Exception $e)
        {
            throw new exceptions\comicManagerException('Invalid date: ' . $date, 0, $e);
        }
    }

    /**
     * Create a release instance from comics
     * @param comicmanager $comicmanager
     * @param array $data
     * @param string $site Site slug
     * @return Release Release instance
     * @throws comicManagerException
     */
    public static function from_comics(comicmanager $comicmanager, array $data, string $site): Release
    {
        $date = self::parse_date($data['pub_date']);
        if(empty($data['images']))
            throw new exceptions\ComicInvalidArgumentException('No image in provided data');

        $info = [
            'site' => $site, 'date_obj' => $date,
            'image_url' => $data['images'][0]['file'],
            'title' => $data['images'][0]['title']];
        return new self($comicmanager, $info);
    }

    /**
     * Create a release instance from date and site slug
     * @param comicmanager $comicmanager Comicmanager instance
     * @param string $date Release date
     * @param string $site Release site slug
     * @return Release Release instance
     * @throws exceptions\comicManagerException Invalid date
     */
    public static function from_date(comicmanager $comicmanager, string $site, string $date): Release
    {
        $release = new static($comicmanager, ['date' => $date, 'site' => $site]);
        $release->load_db();
        return $release;
    }

    public function values(): array
    {
        $values = [];
        foreach ($this->comic->fields as $field)
        {
            $values[$field] = $this->$field;
        }
        return $values;
    }
}