<?php


namespace datagutten\comicmanager;


use datagutten\comicmanager\elements\Release;
use datagutten\comicmanager\exceptions\imageNotFound;
use datagutten\comics_tools\comics_api_client as comics;
use datagutten\tools\files\files as file_tools;

class image
{
    /**
     * @var files
     */
    private files $files;
    public bool $debug = false;

    public Release $release;
    /**
     * @var string Image URL
     */
    public string $url;
    /**
     * @var string Image file if local
     */
    public string $file;

    public static string $image_proxy_path = '/comicmanager/image.php';
    /**
     * @var string Local file path
     */
    private string $file_path;


    /**
     * image constructor.
     * @param string $url Image URL
     */
    function __construct($url)
    {
        $this->url = $url;
    }

    public static function from_file($file)
    {
        $url = self::file_proxy($file);
        $image = new self($url);
        $image->file = $file;
        return $image;
    }

    /**
     * @param $url
     * @return image
     */
    public static function from_url($url)
    {
        return new self($url);
    }

    /**
     * @param string $site
     * @param string $date Date in YMD format
     * @param comicmanager $comicmanager
     * @return image
     * @throws imageNotFound
     */
    public static function from_date($site, $date, comicmanager $comicmanager)
    {
        if(!empty($comicmanager->comics))
        {
            try
            {
                $url = self::comics_lookup($comicmanager->comics, $site, $date);
                return new self($url);
            }
            catch (comics\exceptions\ComicsException $e_comics)
            {
            }
        }

        if(!empty($comicmanager->files))
        {
            try
            {
                $file = self::date_file($comicmanager->files, $site, $date);
                return self::from_file($file);
            }
            catch (imageNotFound $e_file)
            {
            }
        }

        $e = new imageNotFound('Image not found', 0);
        if (isset($e_comics))
            $e->e_comics = $e_comics;
        if(isset($e_file))
            $e->e_file = $e_file;
        throw $e;


        /*if(!empty($e_comics))
            throw new imageNotFound('Image not found on comics', 0, $e_comics);
        if(!empty($e))
            throw new imageNotFound('Image not found', 0, $e);
        else
            throw new imageNotFound('No valid image sources found');*/
    }

    /**
     * @param $site
     * @param $key
     * @param $keyfield
     * @param comicmanager $comicmanager
     * @return image
     * @throws imageNotFound
     */
    public static function from_key($site, $key, $keyfield, comicmanager $comicmanager)
    {
        $file = self::key_file($comicmanager->files, $site, $key, $keyfield);
        return self::from_file($file);
    }

    public static function file_proxy($file)
    {
        return self::$image_proxy_path . '?file=' . urlencode($file);
    }

    /**
     * Check if the strip is found on comics
     * @param comics\ComicsAPICache $comics
     * @param string $site
     * @param string $date
     * @return string Image URL
     * @throws comics\exceptions\HTTPError HTTP error
     * @throws comics\exceptions\NoResultsException No release found
     * @throws comics\exceptions\ComicsException
     */
    public static function comics_lookup(comics\ComicsAPICache $comics, $site, $date)
    {
        $comics_date = preg_replace('/([0-9]{4})([0-9]{2})([0-9]{2})/', '$1-$2-$3', $date); //Rewrite date for comics
        $release = $comics->releases_date_cache($site, $comics_date);
        return $release['file'];
    }

    /**
     * Get local file by site and date
     * @param files $files
     * @param $site
     * @param $date
     * @return string Local image file
     * @throws imageNotFound
     */
    public static function date_file(files $files, $site, $date)
    {
        $path =  $files->filename($site, $date);
        return files::typecheck($path);
    }

    /**
     * Get local file by comic and key
     * @param files $files
     * @param $site
     * @param $key
     * @param string $keyfield
     * @return string Local image file
     * @throws imageNotFound
     */
    public static function key_file(files $files, $site, $key, $keyfield='id')
    {
        if($keyfield=='customid')
            $key = 'custom_'.$key;

        $path = file_tools::path_join($files->file_path, $site, $key);
        return files::typecheck($path);
    }

    /**
     * Get image from local file
     * @return string Local file path
     * @throws imageNotFound Image not found
     */
    function local_file()
    {
        $local_paths = [];
        if(!empty($this->release->date)) //Show strip by date
            $local_paths[] = $this->files->filename($this->release->site, $this->release->date);
        if(!empty($this->release->id))
            $local_paths[] = file_tools::path_join($this->file_path, $this->release->site, $this->release->id);
        if(!empty($this->release->customid))
            $local_paths[] = file_tools::path_join($this->file_path, $this->release->site, 'custom_'.$this->release->customid);

        foreach($local_paths as $path)
        {
            try {
                return files::typecheck($path);
            } catch (exceptions\imageNotFound $e) //File not found
            {
                continue;
            }
        }

        throw new exceptions\imageNotFound('No images found');
    }
}