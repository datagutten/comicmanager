<?php


namespace datagutten\comicmanager;


use comics;
use Exception;
use FileNotFoundException;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;

class comicmanager extends core
{
    /**
     * @var comics
     */
    public $comics;
    /**
     * @var string Media for comics
     */
    public $comics_media;
    /**
     * @var array Array with info about comics
     */
    public $comic_info;
    /**
     * @var array Array with info about the current comic
     */
    public $info;
    /**
     * @var array Array with info about comics, default value from db
     */
    public $comic_info_db;
    /**
     * @var array Comic sources
     */
    public $sources=array();
    /**
     * @var comics_cache
     */
    public $comics_cache;
	/**
	 * @var files File management
	 */
    public $files;

	public function __construct()
	{
        parent::__construct();
        if(isset($this->config['comics_site']) && isset($this->config['comics_key']))
        {
            $this->comics_cache = new comics_cache($this->config['comics_site'],$this->config['comics_key']);
            if(isset($comics_media))
                $this->comics_media=$comics_media;
            $this->sources['comics']='Jodal comics';
            $this->comics = $this->comics_cache->comics;
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
        return $this->db->query($query, $fetch);
    }

    /**
     * Get all available comics and populate $this->comic_list
     *
     * @return array Array with comics, key is id, value is display name
     * @throws Exception No comics in database
     */
    public function comic_list()
	{
		$st=$this->query("SELECT id,name FROM comic_info ORDER BY name", null);
		if($st->rowCount()===0)
			throw new Exception('No comics in database');

		return $st->fetchAll(PDO::FETCH_KEY_PAIR);
	}

    /**
     * Find valid sites for a comic
     * @return array
     */
    function sites()
	{
		return $this->query(sprintf('SELECT DISTINCT site FROM %s',$this->info['id']),'all_column');
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
            $st = $this->query(sprintf('SELECT %s FROM %s_categories WHERE visible=1 ORDER BY name',$fields, $this->info['id']), PDO::FETCH_ASSOC);
        else
            $st = $this->query(sprintf('SELECT %s FROM %s_categories ORDER BY name', $fields, $this->info['id']), PDO::FETCH_ASSOC);

        if($return_object)
            return $st;
        else
            return $st->fetchAll(PDO::FETCH_KEY_PAIR);
	}

    /**
     * Get information about a comic
     *
     * @param string $comic Comic id
     * @param string $key_field Override the default key field
     * @return array Array with comic information
     */
    public function comicinfo($comic, $key_field=null)
    {
        if(!preg_match('/^[a-z]+$/',$comic))
            throw new InvalidArgumentException('Invalid comic id: '.$comic);

        $info=$this->query(sprintf("SELECT * FROM comic_info WHERE id='%s'",$comic),'assoc');

        if(empty($info))
            throw new InvalidArgumentException('Unknown comic id: '.$comic);

        $this->comic_info_db[$info['id']]=$info;

        if(strpos($info['possible_key_fields'],',')!==false)
            $info['possible_key_fields']=explode(',',$info['possible_key_fields']);
        else
            $info['possible_key_fields']=(array)$info['possible_key_fields'];
        //Default key field is overridden
        if(!empty($key_field))
        {
            if(array_search($key_field,$info['possible_key_fields'])===false && $key_field!=='uid')
                throw new InvalidArgumentException('Invalid key field: '.$key_field);
            else
                $info['keyfield']=$key_field;
        }

        $this->comic_info[$info['id']]=$info;
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
        if(!$this->hasColumn($this->info['id'], 'customid'))
            throw new InvalidArgumentException(sprintf('%s does not have customid', $this->info['name']));
		return $this->query($q="SELECT max(customid)+1 FROM {$this->info['id']}",'column');
	}

    /**
     * Get a comic release
     * @param $args
     * @param bool $return_pdo Return PDOStatement
     * @return array|bool|mixed|PDOStatement|string|null
     */
    function get($args, $return_pdo = false)
    {
        if(empty($this->info['id']))
            throw new InvalidArgumentException('Comic not set');
        if(!empty($args['uid']) && is_numeric($args['uid']))
        {
            $st = $this->query(sprintf('SELECT * FROM %s WHERE uid=%d', $this->info['id'], $args['uid']));
            //return $st->fetch(PDO::FETCH_ASSOC);
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

            $this->db->execute($st, $values);
        }
        if($return_pdo)
            return $st;
        else
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

        return $this->db->execute($st, array($release[$this->info['keyfield']]), 'assoc');
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
            $this->db->execute($st, $values);
            //$debug_q=vsprintf(str_replace('?','%s',$q),$values);
        }
    }
}
