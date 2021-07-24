<?php


namespace datagutten\comicmanager\elements;


use datagutten\comicmanager\comicmanager;
use datagutten\comicmanager\exceptions;
use datagutten\comicmanager\exceptions\comicManagerException;
use datagutten\comicmanager\exceptions\ImageNotFound;
use DateTime;
use Exception;

class Release
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

    public ?string $title;

    /**
     * @var ?ImageNotFound
     */
    public ?ImageNotFound $image_error = null;
    /**
     * @var comicmanager
     */
    public comicmanager $comicmanager;
    /**
     * @var Image|null
     */
    public ?Image $image;
    /**
     * @var DateTime Release date
     */
    public DateTime $date_obj;

    /**
     * Release constructor.
     * @param comicmanager $comicmanager
     * @param array $fields
     * @param bool $load_image
     * @throws comicManagerException
     */
    function __construct(comicmanager $comicmanager, array $fields, $load_image = true)
    {
        $this->comicmanager = $comicmanager;
        foreach ($fields as $field => $value)
        {
            $this->$field = $value;
        }
        if (isset($this->date) && !isset($this->date_obj))
            $this->date_obj = self::parse_date($this->date);
        elseif (!isset($this->date) && isset($this->date_obj))
            $this->date = $this->date_obj->format('Ymd');

        if ($load_image)
            $this->image = $this->get_image();
    }

    /**
     * Get image for the release
     * @return ?Image
     */
    function get_image(): ?Image
    {
        try
        {
            if(!empty($this->image_url))
                return Image::from_url($this->image_url);
            if(!empty($this->image_file))
                return Image::from_file($this->image_file, $this->comicmanager);
            list($key_field, $key) = $this->find_key();
            if(!empty($this->site) && !empty($this->date))
                return Image::from_date($this->site, $this->date, $this->comicmanager);
            elseif(!empty($key))
            {
                return Image::from_key(
                    $this->site,
                    $key,
                    $key_field,
                    $this->comicmanager);
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
        $fields = $this->comicmanager->info['possible_key_fields'];
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
        $key_field = $this->comicmanager->info['keyfield'];
        return property_exists($this, $key_field) && !empty($this->$key_field);
    }

    /**
     * Get the grouping key for the release
     * @return ?string
     */
    function key(): ?string
    {
        $key_field = $this->comicmanager->info['keyfield'];
        if ($this->has_key())
            return $this->$key_field;
        else
            return null;
    }

    /**
     * @throws exceptions\ReleaseNotFound
     */
    public function load_db()
    {
        $fields = [];
        foreach (['date', 'site', 'id'] as $field)
        {
            if(!empty($this->$field))
                $fields[$field] = $this->$field;
        }

        $info = $this->comicmanager->get($fields);
        if(empty($info))
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
}