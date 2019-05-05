<?php
require 'tools/autoload.php';
require 'class_jodal_comics.php';
require 'vendor/autoload.php';
require 'tools/DOMDocument_createElement_simple.php';

class comicmanager
{
    /**
     * @var string Error string
     */
    public $error;
    /**
     * @var pdo_helper
     */
    public $db;
    /**
     * @var bool|string
     */
    public $filepath;
    /**
     * @var
     */
    public $picture_host;
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
     * @var array
     */
    public $sources=array();
    /**
     * @var Twig_Environment
     */
    public $twig;

    /**
     * @var DOMDocumentCustom
     */
    public $dom;
    /**
     * @var string Web site root directory
     */
    public $root='comicmanager';
    /**
     * @var array Array with prepared queries
     */
    private $queries;

	public function __construct()
	{
	    $loader = new Twig\Loader\FilesystemLoader(array('templates', 'management/templates'), __DIR__);
        $this->twig = new Twig\Environment($loader, array('debug' => true, 'strict_variables' => true));

        $this->db=new pdo_helper;
        try {
            $this->db->connect_db_config(__DIR__.'/config_db.php');
        }
        catch (Exception|FileNotFoundException $e)
        {
            $this->twig->render('error.twig', array('error'=>$e->getMessage()));
        }

        $this->dom=new DOMDocumentCustom;
        $this->dom->formatOutput=true;

        require 'config.php';
		error_reporting(E_ALL);
		ini_set('display_errors',1);

		if(isset($comics_site) && isset($comics_key))
		{

			$this->comics=new comics($comics_site,$comics_key);
			if(isset($comics_media))
			    $this->comics_media=$comics_media;
			$this->sources['comics']='Jodal comics';
		}
		if(isset($filepath))
        {
            if(!file_exists($filepath))
                trigger_error("Invalid image file path: $filepath",E_USER_ERROR);
            else
            {
                $this->filepath=realpath($filepath);
                $this->sources['file']='Local files';
            }
        }
		if(empty($this->sources))
		    throw new exception('No valid sources configured');

		if(isset($picture_host))
			$this->picture_host=$picture_host;
	}

    /**
     * @param $query
     * @param int $fetch PDO::FETCH_CLASS
     * @return PDOStatement|array|string|null
     */
	public function query($query, $fetch=null)
    {
        try {
            return $this->db->query($query, $fetch);
        }
        catch (PDOException $e)
        {
            try {
                die($this->render('error.twig', array('title'=>'SQL error', 'error'=>$e->getMessage())));
            }
            catch (\Twig\Error\Error $e)
            {
                die($e->getMessage()."\n".$e->getTraceAsString());
            }
        }
    }

    /**
     * Get all available comics and populate $this->comic_list
     *
     * @return array Array with comics, key is id, value is display name
     * @throws Exception
     */
    public function comic_list()
	{
		$st=$this->db->query("SELECT id,name FROM comic_info ORDER BY name", null);
		if($st->rowCount()===0)
			throw new Exception('No comics in database');

		return $st->fetchAll(PDO::FETCH_KEY_PAIR);
	}

	//Display links to select a comic
	public function select_comic()
	{
	    $context = array(
	        'comics'=>$this->comic_list(),
            'title'=>'Select comic',
            'root'=>$this->root);
	    return $this->twig->render('select_comic.twig', $context);
	}

    /**
     * Find valid sites for a comic
     *
     * @return array
     * @throws Exception
     */
    function sites()
	{
		return $this->db->query(sprintf('SELECT DISTINCT site FROM %s',$this->info['id']),'all_column');
	}

    /**
     * Get all categories for a comic
     * @param bool $only_visible Return only categories marked as visible
     * @param bool $return_object Return the PDOStatement object
     * @return array|PDOStatement
     */
	public function categories($only_visible=false, $return_object=false)
	{
        if($this->info['has_categories']!='1')
            return array();
        if($return_object)
            $fields = '*';
        else
            $fields = 'id, name';

        if($only_visible)
            $st = $this->query(sprintf('SELECT %s FROM %s_categories WHERE visible=1 ORDER BY name ASC',$fields, $this->info['id']), PDO::FETCH_ASSOC);
        else
            $st = $this->query(sprintf('SELECT %s FROM %s_categories ORDER BY name ASC', $fields, $this->info['id']), PDO::FETCH_ASSOC);

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
     * @throws Exception
     */
    public function comicinfo($comic, $key_field=null)
    {
        if(!preg_match('/^[a-z]+$/',$comic))
            throw new Exception('Invalid comic id: '.$comic);

        $info=$this->db->query(sprintf("SELECT * FROM comic_info WHERE id='%s'",$comic),'assoc');

        if(empty($info))
            throw new Exception('Unknown comic id: '.$comic);

        $this->comic_info_db[$info['id']]=$info;

        if(strpos($info['possible_key_fields'],',')!==false)
            $info['possible_key_fields']=explode(',',$info['possible_key_fields']);
        else
            $info['possible_key_fields']=(array)$info['possible_key_fields'];
        //Default key field is overridden
        if(!empty($key_field))
        {
            if(array_search($key_field,$info['possible_key_fields'])===false && $key_field!=='uid')
                throw new Exception('Invalid key field: '.$key_field);
            else
                $info['keyfield']=$key_field;
        }

        $this->comic_info[$info['id']]=$info;
        $this->info = $info;
        return $info;
    }

	public function comicinfo_get()
	{
		if(isset($_GET['comic']))
		{
			if(isset($_GET['keyfield'])) //Override default key field for the comic
				return $this->comicinfo($_GET['comic'],$_GET['keyfield']);
			else
				return $this->comicinfo($_GET['comic']);
		}
		else //No comic selected, display comic selection
		{
			echo $this->select_comic();
			return false;
		}
	}

    /**
     * Renders a template.
     *
     * @param string $name    The template name
     * @param array  $context An array of parameters to pass to the template
     *
     * @return string The rendered template
     *
     */
    public function render($name, $context)
    {
        $context = array_merge($context, array(
            'root'=>$this->root,
            'comic'=>$this->info));
        try {
            return $this->twig->render($name, $context);
        }
        catch (\Twig\Error\Error $e) {

            //$trace = sprintf('<pre>%s</pre>', $e->getTraceAsString());
            $msg = "Error rendering template:\n" . $e->getMessage();
            try {
                die($this->twig->render('error.twig', array(
                    'root'=>$this->root,
                    'comic'=>$this->info,
                    'title'=>'Rendering error',
                    'error'=>$msg)
                ));
            }
            catch (\Twig\Error\Error $e_e)
            {
                $msg = sprintf("Original error: %s\n<pre>%s</pre>\nError rendering error template: %s\n<pre>%s</pre>",
                    $e->getMessage(), $e->getTraceAsString(), $e_e->getMessage(), $e_e->getTraceAsString());
                die($msg);
            }
            //die($this->render($this->render()))
        }
    }

    public function prepare_queries()
    {
        if(empty($this->info['id']))
            throw new Exception('Comic info not found');

        $comic=$this->info['id'];
        $comic_info=$this->info;
        $this->queries[$this->info['id']]['keyfield']=
            $this->db->prepare($q=sprintf('SELECT * FROM %s WHERE %s=?',
                $this->info['id'], $comic_info['keyfield']));

        $this->queries[$this->info['id']]['date_and_site']=
            $this->db->prepare($q=sprintf('SELECT * FROM %s WHERE date=? and site=?', $comic));

        $this->queries[$this->info['id']]['insert_keyfield']=
            $this->db->prepare($q=sprintf('INSERT INTO %s (%s) VALUES ?', $comic, $comic_info['keyfield']));

    }

	public function typecheck($filename) //Try different extensions for a file name
	{
		$types=array('jpg','gif','png');
		foreach($types as $type)
		{
			if(file_exists($filename.'.'.$type))
			{
				$file=$filename.".$type";
				break;
			}
		}
		if(!isset($file)) //File not found
            throw new Exception('Image not found by date: '.$filename);

		return $file;
	}
	public function showpicture($row,$keyfield=false,$comic=false,$noheader=false)
	{
		$div=$this->dom->createElement_simple('div',false,array('class'=>'release'));
		if(!is_array($row))
			throw new exception('Release is not array');
		if($comic===false)
			$comic=$this->info['id'];
		if($keyfield===false)
			$keyfield=$this->comic_info[$comic]['keyfield'];

		if(!$noheader) //Make header
		{
			if(!empty($row['date']))
				$this->dom->createElement_simple('span',$div,false,$row['date'].' - ');

			if(isset($row[$keyfield]))
			{
				$urlfields=array('comic'=>$comic,'view'=>'singlestrip','keyfield'=>$keyfield,'value'=>$row[$keyfield]);
				$this->dom->createElement_simple('a',$div,array('href'=>'/comicmanager/showcomics.php?'.http_build_query($urlfields,'','&')),$row[$keyfield]);
			}
			else
				$this->dom->createElement_simple('span',$div,false,$row['uid']);
			if(isset($row['tittel']))
				$this->dom->createElement_simple('span',$div,false,' - '.$row['tittel']);
			$this->dom->createElement_simple('span',$div,false,' - '.$row['site']);
			$this->dom->createElement_simple('br',$div);
		}

		$image=$this->imagefile($row);
		if($image===false)
		{
			if(empty($this->error))
				$this->dom->createElement_simple('p',$div,false,'No image found');
			else
				$this->dom->createElement_simple('p',$div,false,$this->error);
		}
		else
		{
			if(substr($image,0,4)!='http')
				$image="/comicmanager/image.php?file=".$image;
			$a=$this->dom->createElement_simple('a',$div,array('href'=>$image));
			$this->dom->createElement_simple('img',$a,array('src'=>$image,'style'=>'max-width: 1000px; max-height: 400px'));
		}
		//$this->dom->createElement_simple('pre',$div,false,print_r($row,true));
		//$this->dom->createElement_simple('pre',$div,false,$image);
		return $div;
	}
    public function imagefile($row) //Find the image file for a database row
    {
        if(!empty($row['date'])) //Show strip by date
        {
            $comics_date=preg_replace('/([0-9]{4})([0-9]{2})([0-9]{2})/','$1-$2-$3',$row['date']); //Rewrite date for comics
            if(is_object($this->comics)) {
                try {
                    //Check if the strip is found on comics
                    $image=$this->comics_release_single_cache($row['site'],$comics_date);
                }
                catch (Exception $e_comics) {
                    //Image not found on comics, try to find local file
                    try {
                        $image = $this->typecheck($filename = $this->filename($row['site'], $row['date']));
                    }
                    catch (Exception $e_file) //File not found, re-throw exception from comics
                    {
                        throw $e_comics;
                    }
                }
            }
        }
		else //Show strip by id
		{
			if(!empty($row['id']))
				$image=$this->typecheck($this->filepath."/{$row['site']}/{$row['id']}");
			if(isset($row['customid']) && (!isset($image) || $image===false)) //Image not found by id, try customid
				$image=$this->typecheck($this->filepath."/{$row['site']}/custom_{$row['customid']}");
		}
		if(empty($image))
			return false;
		else
			return $image;
	}

	function comics_release_single_cache($slug,$date)
	{
		$st_select=$this->db->prepare("SELECT file FROM comics_cache WHERE slug=? AND date=? AND site=?");
		$st_insert=$this->db->prepare("INSERT INTO comics_cache (checksum,slug,date,file,site) VALUES (?,?,?,?,?)");
        //Try to find image in local cache
        $this->db->execute($st_select, array($slug,$date,$this->comics->site));
		if($st_select->rowCount()==0)
		{
			$image_url=$this->comics->release_single($slug,$date); //Query comics to get image url
			if(empty($image_url)) //Release not found on comics
            {
                $this->error=$this->comics->error;
                return false;
            }
            //Extract image hash from URL
            preg_match(sprintf('^.+(%s.+/([a-f0-9]+)(?:_[A-Za-z0-9]+)?\..+)^', $slug), $image_url, $fileinfo);
            if(empty($fileinfo))
                throw new Exception('Invalid URL: '.$image_url);
			$st_insert->execute(array($fileinfo[2],$slug,$date,$fileinfo[1],$this->comics->site)); //Add image hash to local cache table
			return $image_url;
		}
		return $this->comics_media.'/'.$st_select->fetch(PDO::FETCH_COLUMN);
	}

	function filename($site,$date,$create_dir=false)
	{
		//Files are stored in [filepath]/site/month/date

		$dir=$this->filepath.'/'.$site.'/'.substr($date,0,6);

		if($create_dir!==false && !file_exists($dir))
			mkdir($dir,0777,true);
		return $dir.'/'.$date;
	}
	function next_customid()
	{
		return $this->db->query($q="SELECT max(customid)+1 FROM {$this->info['id']}",'column');
	}

    function get($args)
    {
        if(!empty($args['uid']) && is_numeric($args['uid']))
        {
            $st = $this->query(sprintf('SELECT * FROM %s WHERE uid=%d', $this->info['id'], $args['uid']));
            return $st->fetch(PDO::FETCH_ASSOC);
        }

        $valid_fields = array('date', 'site', 'id', 'key');
        $valid_fields = array_merge($valid_fields, $this->info['possible_key_fields']);
        unset($args['file']);
        $where='';
        $values = array();
        foreach($args as $field=>$value)
        {
            if(array_search($field, $valid_fields)===false)
                continue;
            //throw new Exception('Invalid field '.$field);

            $where .= sprintf('%s=? AND ', $field);
            $values[]=$value;
        }
        $where = substr($where, 0, -5);

        $q = sprintf('SELECT * FROM %s WHERE %s', $this->info['id'], $where);
        $st = $this->db->prepare($q);

        $this->db->execute($st, $values);
        return $st->fetch(PDO::FETCH_ASSOC);
    }


    /**
     * Get newest release of a strip
     *
     * @param array $release Array with release info
     * @return array
     * @throws Exception
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
     * @throws Exception
     */
    function add_or_update($args=array('date'=>null, 'site'=>'null', 'id'=>'null', 'key'=>null, 'category'=>null)) {

        $fields = array_filter($args);
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
            $merged = array_merge($fields, array_filter($release));
            $missing = array_diff_assoc($merged, $release); //Values that should be updated
            $sets='';
            $values = array();
            foreach ($missing as $field=>$value)
            {
                $sets.=sprintf('%s=?,', $field);
                $values[] = $value;
            }
            $sets=substr($sets, 0, -1);

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
                throw new exception('No valid key');

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
