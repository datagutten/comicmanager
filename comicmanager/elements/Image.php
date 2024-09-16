<?php


namespace datagutten\comicmanager\elements;


use datagutten\comicmanager\comicmanager;
use datagutten\comicmanager\exceptions;
use datagutten\comicmanager\files;
use datagutten\comics_tools\comics_api_client as comics;
use datagutten\tools\files\files as file_tools;
use DateTime;

class Image
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

    /**
     * @var string Local file path
     */
    private string $file_path;

    /**
     * image constructor.
     * @param string $url Image URL
     */
    function __construct(string $url)
    {
        $this->url = $url;
    }

    /**
     * Create image object from a local file
     * @param string $file File path
     * @param ?comicmanager $comicmanager
     * @return Image Image object
     * @throws exceptions\ImageNotFound Image file not found
     */
    public static function from_file(string $file, comicmanager $comicmanager=null): Image
    {
        if(!file_exists($file))
            throw new exceptions\ImageNotFound(sprintf('Image file "%s" not found', $file));

        if(!empty($comicmanager))
        {
            if(strpos($file, $comicmanager->files->file_path) === 0)
            {
                $url = str_replace($comicmanager->files->file_path, $comicmanager->web_image_root, $file);
                $url = str_replace('\\', '/', $url);
            }
            else
                $url = self::file_proxy($file, $comicmanager->web_root);
        }
        else
            $url = self::file_proxy($file);

        $image = new static($url);
        $image->file = $file;
        return $image;
    }

    /**
     * Create image object from a URL
     * @param string $url Image URL
     * @return Image Image object
     */
    public static function from_url(string $url): Image
    {
        return new self($url);
    }

    /**
     * Create image object from date
     * @param string $site Release site slug
     * @param DateTime $date Date as DateTime object
     * @param comicmanager $comicmanager
     * @return Image
     * @throws exceptions\ImageNotFound
     */
    public static function from_date(string $site, DateTime $date, comicmanager $comicmanager): Image
    {
        if(!empty($comicmanager->comics))
        {
            try
            {
                $url = self::comics_lookup($comicmanager->comics, $site, $date);
                if(!empty($url))
                    return self::from_url($url);
            }
            catch (comics\exceptions\ComicsException $e_comics)
            { // Not found on comics
            }
        }

        if(!empty($comicmanager->files))
        {
            try
            {
                $file = self::date_file($comicmanager->files, $site, $date);
                return self::from_file($file, $comicmanager);
            }
            catch (exceptions\ImageNotFound $e_file)
            {
            }
        }

        $e = new exceptions\ImageNotFound('Image not found', 0, $e_file ?? $e_comics ?? null);
        if (isset($e_comics))
            $e->e_comics = $e_comics;
        if(isset($e_file))
            $e->e_file = $e_file;
        throw $e;
    }

    /**
     * Create image object from key
     * @param string $site
     * @param string $key
     * @param string $keyfield
     * @param comicmanager $comicmanager
     * @return Image Image object
     * @throws exceptions\ImageNotFound
     */
    public static function from_key(string $site, string $key, string $keyfield, comicmanager $comicmanager): Image
    {
        $file = self::key_file($comicmanager->files, $site, $key, $keyfield);
        return self::from_file($file, $comicmanager);
    }

    public static function file_proxy($file, $web_root = '/comicmanager'): string
    {
        return $web_root . '/image.php?file=' . urlencode($file);
    }

    /**
     * Check if the strip is found on comics
     * @param comics\ComicsAPICache $comics
     * @param string $site
     * @param DateTime $date
     * @return string Image URL
     * @throws comics\exceptions\HTTPError HTTP error
     * @throws comics\exceptions\NoResultsException No release found
     * @throws comics\exceptions\ComicsException
     */
    public static function comics_lookup(comics\ComicsAPICache $comics, string $site, DateTime $date): string
    {
        $release = $comics->releases_date_cache($site, $date->format('Y-m-d'));
        return $release['file'];
    }

    /**
     * Get local file by site and date
     * @param files $files
     * @param string $site
     * @param DateTime $date
     * @return string Local image file
     * @throws exceptions\ImageNotFound File not found
     */
    public static function date_file(files $files, string $site, DateTime $date): string
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
     * @throws exceptions\ImageNotFound
     */
    public static function key_file(files $files, $site, $key, $keyfield='id'): string
    {
        if($keyfield=='customid')
            $key = 'custom_'.$key;

        $path = file_tools::path_join($files->file_path, $site, $key);
        return files::typecheck($path);
    }
}