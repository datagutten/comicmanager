<?php


namespace datagutten\comicmanager;

use datagutten\comicmanager\elements\Comic;
use datagutten\comicmanager\exceptions\comicManagerException;
use datagutten\comics_tools\comics_api_client as comics;
use datagutten\comics_tools\comics_api_client\exceptions\ComicsException;
use FileNotFoundException;
use PDO;

class comicmanager extends core
{
    /**
     * @var comics\ComicsAPICache
     */
    public comics\ComicsAPICache $comics;
    /**
     * @var array Array with info about comics
     */
    public array $comic_info;
    /**
     * @var Comic Information about the current comic
     */
    public Comic $info;
    /**
     * @var array Comic sources
     */
    public array $sources=array();
	/**
	 * @var files File management
	 */
    public files $files;
    /**
     * @var string Web site root path
     */
    public $web_root;
    /**
     * @var elements\Releases Release manager class
     */
    public elements\Releases $releases;
    /**
     * @var elements\Strips Strip manager class
     */
    public elements\Strips $strips;
    /**
     * @var string Image root URL
     */
    public $web_image_root;

    /**
     * @var Lists List parser class
     */
    public Lists $lists;

    /**
     * comicmanager constructor.
     * @param ?array $config
     * @throws FileNotFoundException File path not found
     * @throws comicManagerException
     */
    public function __construct(array $config=null)
	{
        parent::__construct($config);
        if(!empty($this->config['comics']))
        {
            try
            {
                $this->comics = new comics\ComicsAPICache($this->config['comics']);
                $this->sources['comics']='Jodal comics';
            }
            catch (ComicsException $e)
            {
                throw new exceptions\comicManagerException('Error initializing comics', 0, $e);
            }
        }

        if(isset($this->config['file_path']))
        {
        	$this->files = new files($this->config['file_path']);
            $this->sources['file']='Local files';
        }

        if(isset($this->config['list_path']))
        {
            $this->lists = new Lists($this->config['list_path']);
            $this->lists->comicmanager = $this;
        }

        $this->web_root = $config['web_root'] ?? '/comicmanager';
        $this->web_image_root = $config['web_image_root'] ?? $this->web_root . '/images';
    }

    /**
     * Get all available comics and populate $this->comic_list
     *
     * @return array Array with comics, key is id, value is display name
     * @throws comicManagerException No comics in database
     */
    public function comic_list(): array
    {
		$st=$this->db->query("SELECT id,name FROM comic_info ORDER BY name");
		if($st->rowCount()===0)
			throw new comicManagerException('No comics in database');

		return $st->fetchAll(PDO::FETCH_KEY_PAIR);
	}

    /**
     * Get information about a comic
     *
     * @param string $comic_id Comic id
     * @param string|null $key_field Override the default key field
     * @return elements\Comic Comic object
     * @throws exceptions\ComicNotFound Comic not found
     * @throws exceptions\DatabaseException Database error
     */
    public function comicinfo(string $comic_id, ?string $key_field=null): elements\Comic
    {
        $info = new Comic($this->config['db'], ['id'=>$comic_id]);
        $info->load_db();

        if(!empty($key_field))
            $info['key_field'] = $key_field;

        $this->comic_info[$info['id']]=$info;
        $this->info = $info;

        $this->strips = new elements\Strips($this);
        $this->releases = new elements\Releases($this);

        return $info;
    }
}
