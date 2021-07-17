<?php


namespace datagutten\comicmanager\elements;


use datagutten\comicmanager\comicmanager;
use datagutten\comicmanager\exceptions;
use datagutten\comicmanager\exceptions\comicManagerException;
use datagutten\comicmanager\exceptions\ImageNotFound;
use DateTime;
use Exception;
use PDOStatement;

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
    public bool $debug = false;

    /**
     * @var ImageNotFound
     */
    public $image_error;
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

    function __construct(comicmanager $comicmanager, array $fields, $load_image = true)
    {
        $this->comicmanager = $comicmanager;
        foreach ($fields as $field => $value)
        {
            $this->$field = $value;
        }
        if($load_image)
            $this->image = $this->get_image();
    }

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
     * @return array[key field, key]
     */
    function find_key()
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
    function has_key()
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

    public function load_db()
    {
        //if(!empty($this->date) && !empty($this->site))
        $fields = [];
        foreach (['date', 'site', 'id'] as $field)
        {
            if(!empty($this->$field))
                $fields[$field] = $this->$field;
        }

        /*$fields = ['date' => $this->date, 'site' => $this->site, 'id' => $this->id];
        $fields = array_filter($fields);*/
        $info = $this->comicmanager->get($fields);
        if(empty($info))
            return;
        foreach ($info as $key => $value)
        {
            $this->$key = $value;
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
        try
        {
            $date = new DateTime($data['pub_date']);
            $date_string = $date->format('Ymd');
        }
        catch (Exception $e)
        {
            throw new comicManagerException($e->getMessage(), 0, $e);
        }
        $info = [
            'site' => $site, 'date' => $date_string, 'date_obj' => $date,
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
        try
        {
            $date_obj = new DateTime($date);
        }
        catch (Exception $e)
        {
            throw new exceptions\comicManagerException('Invalid date: ' . $date, 0, $e);
        }

        $release = new static($comicmanager, ['date' => $date_obj->format('Ymd'), 'site' => $site]);
        $release->date_obj = $date_obj;
        $release->load_db();
        return $release;
    }
}