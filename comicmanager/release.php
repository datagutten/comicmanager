<?php


namespace datagutten\comicmanager;


use datagutten\comicmanager\exceptions\comicManagerException;
use datagutten\comicmanager\exceptions\imageNotFound;
use DateTime;
use Exception;
use PDOStatement;

class release
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
     * @var imageNotFound
     */
    public $image_error;
    /**
     * @var comicmanager
     */
    public comicmanager $comicmanager;
    /**
     * @var image|null
     */
    public ?image $image;

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

    function get_image(): ?image
    {
        try
        {
            if(!empty($this->image_url))
                return image::from_url($this->image_url);
            if(!empty($this->image_file))
                return image::from_file($this->image_file);
            list($key_field, $key) = $this->find_key();
            if(!empty($this->site) && !empty($this->date))
                return image::from_date($this->site, $this->date, $this->comicmanager);
            elseif(!empty($key))
            {
                return image::from_key(
                    $this->site,
                    $key,
                    $key_field,
                    $this->comicmanager);
            }
            else
                throw new imageNotFound('No valid keys');
        }
        catch (imageNotFound $e)
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
     * @return release Release instance
     * @throws comicManagerException
     */
    public static function from_comics(comicmanager $comicmanager, array $data, string $site): release
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
     * @return release Release instance
     */
    public static function from_date(comicmanager $comicmanager, string $date, string $site): release
    {
        return new self($comicmanager, ['date'=>$date, 'site'=>$site]);
    }

}