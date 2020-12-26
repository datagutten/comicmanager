<?php


namespace datagutten\comicmanager;

use datagutten\comicmanager\elements\Comic;
use datagutten\comicmanager\exceptions\comicManagerException;
use datagutten\comics_tools\comics_api_client as comics;
use datagutten\comics_tools\comics_api_client\exceptions\ComicsException;
use InvalidArgumentException;
use PDO;
use pdo_helper;
use PDOException;
use PDOStatement;

class comicmanager extends core
{
    /**
     * @var comics\ComicsAPICache
     */
    public $comics;
    /**
     * @var array Array with info about comics
     */
    public $comic_info;
    /**
     * @var Comic Information about the current comic
     */
    public Comic $info;
    /**
     * @var array Array with info about comics, default value from db
     */
    public $comic_info_db;
    /**
     * @var array Comic sources
     */
    public $sources=array();
	/**
	 * @var files File management
	 */
    public files $files;
    /**
     * @var Queries
     */
    protected Queries $queries;

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
                echo $e->getMessage();
            }
        }

        if(isset($this->config['file_path']))
        {
        	$this->files = new files($this->config['file_path']);
            $this->sources['file']='Local files';
        }
    }

    /**
     * Run a database query
     * @param string $query SQL query
     * @param string|int $fetch Fetch type, passed to fetch method
     * @return PDOStatement|array|string|null
     * @throws PDOException
     */
    public function query($query, $fetch = null)
    {
        $st = $this->db->query($query);
        if(!empty($fetch))
                return pdo_helper::fetch($st, $fetch);
        else
            return $st;
    }

    /**
     * Get all available comics and populate $this->comic_list
     *
     * @return array Array with comics, key is id, value is display name
     * @throws comicManagerException No comics in database
     */
    public function comic_list()
	{
		$st=$this->db->query("SELECT id,name FROM comic_info ORDER BY name");
		if($st->rowCount()===0)
			throw new comicManagerException('No comics in database');

		return $st->fetchAll(PDO::FETCH_KEY_PAIR);
	}

    /**
     * Find valid sites for a comic
     * @return array
     */
    function sites()
	{
		$st = $this->query(sprintf('SELECT DISTINCT site FROM %s',$this->info['id']));
		return $st->fetchAll(PDO::FETCH_COLUMN);
	}

    /**
     * Get all categories for a comic
     * @param bool $only_visible Return only categories marked as visible
     * @param bool $return_object Return the PDOStatement object
     * @return array|PDOStatement
     */
	public function categories($only_visible=false, $return_object=false)
	{
        if($this->info['has_categories']!='1') {
            if ($return_object === false)
                return array();
            else
                return new PDOStatement();
        }

        if($return_object)
            $fields = '*';
        else
            $fields = 'id, name';

        if($only_visible)
            $st = $this->db->query(sprintf('SELECT %s FROM %s_categories WHERE visible=1 ORDER BY name',$fields, $this->info['id']));
        else
            $st = $this->db->query(sprintf('SELECT %s FROM %s_categories ORDER BY name', $fields, $this->info['id']));

        if($return_object)
            return $st;
        else
            return $st->fetchAll(PDO::FETCH_KEY_PAIR);
	}

    /**
     * Get category name from id
     * @param int $category_id Category id
     * @return string Category name
     */
	function category_name(int $category_id)
    {
        $st_category_name = $this->db->prepare(sprintf('SELECT name FROM %s_categories WHERE id=?', $this->info['id']));
        $st_category_name->execute([$category_id]);
        return $st_category_name->fetch(PDO::FETCH_COLUMN);
    }

    /**
     * Get information about a comic
     *
     * @param string $comic_id Comic id
     * @param string|null $key_field Override the default key field
     * @return Comic Array with comic information
     */
    public function comicinfo(string $comic_id, ?string $key_field=null): Comic
    {
        $info = Comic::from_db($this->db, $comic_id);
        if(!empty($key_field))
            $info['key_field'] = $key_field;

        $this->comic_info[$info['id']]=$info;
        $this->queries = new Queries($this->db, $info->id);
        $this->info = $info;
        return $info;
    }


    /**
     * Find the image file for a database row
     * @param array $row Array with release information
     * @return string Image file
     * @throws Exception Image not found
     */
    public function imagefile($row)
    {
        if(!empty($row['date'])) //Show strip by date
        {
            $comics_date=preg_replace('/([0-9]{4})([0-9]{2})([0-9]{2})/','$1-$2-$3',$row['date']); //Rewrite date for comics
            if(is_object($this->comics_cache)) {
                try {
                    //Check if the strip is found on comics
                    $image=$this->comics_cache->comics_release_single_cache($row['site'],$comics_date);
                }
                catch (Exception $e_comics) {
                    //Image not found on comics, try to find local file
                    try {
                        $image = files::typecheck($filename = $this->files->filename($row['site'], $row['date']));
                    }
                    catch (Exception $e_file) //File not found, re-throw exception from comics
                    {
                        throw $e_comics;
                    }
                }
            }
            elseif($this->debug)
                echo "Comics not available\n";
        }
		else //Show strip by id
		{
			if(!empty($row['id']))
                $image = files::typecheck($this->files->file_path . "/{$row['site']}/{$row['id']}");
			if(isset($row['customid']) && (!isset($image) || $image===false)) //Image not found by id, try customid
                $image = files::typecheck($this->files->file_path . "/{$row['site']}/custom_{$row['customid']}");
		}
		if(empty($image))
			return false;
		else
			return $image;
	}

    /**
     * Find first unused custom id
     * @return string
     */
	function next_customid()
	{
        if(!$this->db_utils->hasColumn($this->info['id'], 'customid'))
            throw new InvalidArgumentException(sprintf('%s does not have customid', $this->info['name']));
		$st = $this->query("SELECT max(customid)+1 FROM {$this->info['id']}");
		return $st->fetch(PDO::FETCH_COLUMN);
	}

    /**
     * Get a comic release
     * @param $args
     * @param bool $return_pdo Return PDOStatement
     * @return array|PDOStatement
     */
    function get($args, $return_pdo = false)
    {
        if(empty($this->info['id']))
            throw new InvalidArgumentException('Comic not set');
        if(!empty($args['uid']) && is_numeric($args['uid']))
        {
            $st = $this->query(sprintf('SELECT * FROM %s WHERE uid=%d', $this->info['id'], $args['uid']));
        }
        else {

            $valid_fields = array('date', 'site', 'id', 'key');
            $valid_fields = array_merge($valid_fields, $this->info['possible_key_fields']);
            unset($args['file']);
            $where = '';
            $values = array();
            foreach ($args as $field => $value) {
                if (array_search($field, $valid_fields) === false)
                    continue;
                //throw new Exception('Invalid field '.$field);

                $where .= sprintf('%s=? AND ', $field);
                $values[] = $value;
            }
            $where = substr($where, 0, -5);

            $q = sprintf('SELECT * FROM %s WHERE %s', $this->info['id'], $where);
            $st = $this->db->prepare($q);
            $st->execute($values);
        }
        if($return_pdo)
            return $st;
        else
            return $st->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get highest and lowest value for a field
     * @param string $field Field name
     * @return array
     */
    function key_high_low($field)
    {
        $q = sprintf('SELECT MIN(%1$s) AS min, MAX(%1$s) AS max FROM %2$s',
            self::clean_value($field),
            $this->info['id']);
        $st = $this->db->query($q);
        return $st->fetch(PDO::FETCH_ASSOC);
    }


    /**
     * Get newest release of a strip
     *
     * @param array $release Array with release info
     * @return array
     * @throws PDOException
     */
    function get_newest($release)
    {
        if(empty($release[$this->info['keyfield']])) //Key field not set, unable to determine newest
            return $release;
        $st=$this->db->prepare(sprintf('SELECT * FROM %s WHERE %s=? ORDER BY date DESC LIMIT 1',
            $this->info['id'],
            $this->info['keyfield']
        ));
        $st->execute(array($release[$this->info['keyfield']]));
        return $st->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Add a comic strip to the database or update it if it exists
     *
     * @param array $args Strip properties
     * array('date'=>null, 'site'=>'null', 'id'=>'null', 'key'=>null, 'category'=>null)
     * @param string $mode Update mode, uid or keyfield to update all releases with a key
     */
    function add_or_update($args, $mode='uid') {

        //$fields = array_filter($args);
        $fields = $args;
        $keyfield=$this->info['keyfield'];
        $release = $this->get($args);

        if(empty($release)) //Not in db, insert it
        {
            $q=sprintf('INSERT INTO %s (%s) VALUES (?%s)',
                $this->info['id'],
                implode(', ', array_keys($fields)),
                str_repeat(',?', count($fields)-1));
            $values=array_values($fields);
        }
        else //Update the strip with new value
        {
            $merged = array_merge(array_filter($release), $fields);
            $missing = array_diff_assoc($merged, $release); //Values that should be updated
            $sets='';
            $values = array();
            foreach ($missing as $field=>$value)
            {
                $sets.=sprintf('%s=?,', $field);
                if($value=='')
                    $value = null;
                $values[] = $value;
            }
            $sets=substr($sets, 0, -1);

            if($mode=='uid')
            {
                if(!isset($args['uid']))
                    throw new InvalidArgumentException('uid must be set when using uid mode');
                $where = 'uid=?';
                $values[] = $args['uid'];
            }
            else
            {
                if(!empty($release[$keyfield])) {
                    $where = sprintf('%s=?', $keyfield);
                    $values[]=$release[$keyfield];
                }
                elseif(!empty($release['date']) && !empty($release['site'])) {
                    $where=sprintf('date=? AND site=?');
                    $values[]=$release['date'];
                    $values[]=$release['site'];
                }
                else
                    throw new InvalidArgumentException('No valid key');
            }

            if(!empty($sets)) {
                $q = sprintf('UPDATE %s SET %s WHERE %s', $this->info['id'], $sets, $where);
            }
        }

        if(!empty($q)){
            $st = $this->db->prepare($q);
            $st->execute($values);
            //$debug_q=vsprintf(str_replace('?','%s',$q),$values);
        }
    }
}
