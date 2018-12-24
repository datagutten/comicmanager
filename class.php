<?php
class comicmanager
{
	public $error;
	public $db;
	public $filepath;
	public $picture_host;
	public $comics;
	public $comics_media;
	public $comic; //Current comic
	public $comic_list; //Array with a list of all comics, id as key, name as value
	public $comic_info; //Array with info about comics
	public $comic_info_db; //Array with info about comics, default value from db
	private $queries;

	public function __construct()
	{
		error_reporting(E_ALL);
		ini_set('display_errors',1);
		require 'config.php';

		if(isset($comics_site) && isset($comics_key))
		{
			require_once 'class_jodal_comics.php';
			$this->comics=new comics($comics_site,$comics_key);
			$this->comics_media=$comics_media;
		}
		if(!file_exists($filepath))
			trigger_error("Invalid image file path: $filepath",E_USER_ERROR);
		else
			$this->filepath=realpath($filepath);
		if(isset($picture_host))
			$this->picture_host=$picture_host;

		require 'tools/pdo_helper.class.php';
		$this->db=new pdo_helper;
		$this->db->connect_db_config(__DIR__.'/config_db.php');
		if($this->build_comic_list()===false)
			die($this->error);

		require 'tools/DOMDocument_createElement_simple.php';
		$this->dom=new DOMDocumentCustom;
		$this->dom->formatOutput=true;
	}
	function query($q,$fetch='all')
	{
		return $this->db->query($q,$fetch);
	}
	function execute($st,$parameters,$fetch=false)
	{
		return $this->db->execute($st,$parameters,$fetch);
	}


	//Get all available comics and populate $this->comic_list
	public function build_comic_list()
	{
		$st=$this->db->query("SELECT id,name FROM comic_info ORDER BY name");
		if($st->rowCount()===0)
		{
			$this->error='No comics in database';
			return false;
		}
		$this->comic_list=$st->fetchAll(PDO::FETCH_KEY_PAIR);
	}

	//Display links to select a comic
	public function selectcomic()
	{
		$output="<h2>Select comic:</h2>\n";
		foreach($this->comic_list as $id=>$name)
		{
			$output.="<p><a href=\"?comic={$id}\">{$name}</a></p>\n";
		}
		return $output;
	}

	//Find valid sites for a comic
	function sites($comic=false)
	{
		if($comic===false) //Default to current comic
			$comic=$this->comic;
		elseif(!isset($this->comic_list[$comic]))
		{
			$this->error='Invalid comic: '.$comic;
			return false;
		}
		return $this->db->query(sprintf('SELECT DISTINCT site FROM %s',$comic),'all_column');
	}
	public function categories($only_visible=false)
	{
		if($only_visible===false)
			$st_categories=$this->db->query(sprintf('SELECT id,name FROM %s_categories ORDER BY name ASC',$this->comic));
		else
			$st_categories=$this->db->query(sprintf('SELECT id,name FROM %s_categories WHERE visible=1 ORDER BY name ASC',$this->comic));

		return $st_categories->fetchAll(PDO::FETCH_KEY_PAIR);
	}
	public function comicinfo($comic,$keyfield=false) //Get information about a comic
	{
		if(!preg_match('/^[a-z]+$/',$comic))
		{
			$this->error='Invalid comic id: '.$comic;
			return false;
		}
		$comicinfo=$this->db->query(sprintf("SELECT * FROM comic_info WHERE id='%s'",$comic),'assoc');

		if($comicinfo===false)
			return false;
		if(empty($comicinfo))
		{
			$this->error='Unkown comic id: '.$comic;
			return false;
		}

		$this->comic_info_db[$comicinfo['id']]=$comicinfo;

		if(strpos($comicinfo['possible_key_fields'],',')!==false)
			$comicinfo['possible_key_fields']=explode(',',$comicinfo['possible_key_fields']);
		else
			$comicinfo['possible_key_fields']=(array)$comicinfo['possible_key_fields'];
		//Default key field is overridden
		if($keyfield!==false)
		{
			if(array_search($keyfield,$comicinfo['possible_key_fields'])===false && $keyfield!=='uid')
			{
				$this->error='Invalid key field: '.$keyfield;
				return false;
			}
			else
				$comicinfo['keyfield']=$keyfield;
		}	

		$this->comic=$comicinfo['id'];
		$this->comic_info[$comicinfo['id']]=$comicinfo;
		return $comicinfo;
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
			echo $this->selectcomic();
			return;
		}
	}
	public function prepare_queries()
	{
		$comic=$this->comic;
		$comicinfo=$this->comic_info[$this->comic];
		$this->queries['keyfield']=
			$this->db->prepare($q=sprintf('SELECT * FROM %s WHERE %s=?',
			$this->comic, $comicinfo['keyfield']));

		$this->queries['date_and_site']=
			$this->db->prepare($q=sprintf('SELECT * FROM %s WHERE date=? and site=?', $comic));

		$this->queries['insert_keyfield']=
            $this->db->prepare($q=sprintf('INSERT INTO %s (%s) VALUES ?', $comic, $comicinfo['keyfield']));

	}

	public function typecheck($filename,$typereturn=false) //Try different extensions for a file name
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
			return false;
		if($typereturn===true) //Return only extension
			return $type;
		return $file;
	}
	public function showpicture($row,$keyfield=false,$comic=false,$noheader=false)
	{
		$div=$this->dom->createElement_simple('div',false,array('class'=>'release'));
		if(!is_array($row))
		{
			throw new exception('Release is not array');
			return false;
		}
		if($comic===false)
			$comic=$this->comic;
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
			if(is_object($this->comics)) //Check if the strip is found on comics
				$image=$this->comics_release_single_cache($row['site'],$comics_date);
			if(!isset($image) || $image===false) //Image not found on comics, try to find local file
				$image=$this->typecheck($filename=$this->filename($row['site'],$row['date']));
			if(empty($image))
			{
				$this->error='Image not found by date: '.$filename;
				return false;
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
	public function newest($keyfield,$key) //Find the newest release for a given strip
	{
		$st_newest=$this->db->prepare("SELECT * FROM {$this->comic} WHERE $keyfield=? ORDER BY date DESC LIMIT 1");
		$st_newest->execute(array($key));
		$row=$st_newest->fetch(PDO::FETCH_ASSOC);
		return $row;
	}
	function comics_release_single_cache($slug,$date)
	{
		$st_select=$this->db->prepare("SELECT file FROM comics_cache WHERE slug=? AND date=? AND site=?");
		$st_insert=$this->db->prepare("INSERT INTO comics_cache (checksum,slug,date,file,site) VALUES (?,?,?,?,?)");
		$st_select->execute($params=array($slug,$date,$this->comics->site)); //Try to find image in local cache
		if($st_select->rowCount()==0)
		{
			$image_url=$this->comics->release_single($slug,$date); //Query comics to get image url
			if($image_url===false) //Release not found on comics
				return false;
			preg_match("^.+($slug.+/([a-f0-9]+)\..+)^",$image_url,$fileinfo); //Extract image hash from URL
			$st_insert->execute(array($fileinfo[2],$slug,$date,$fileinfo[1],$this->comics->site)); //Add image hash to local cache table
			return $image_url;
		}
		return $this->comics_media.'/'.$st_select->fetch(PDO::FETCH_COLUMN);
	}
	function filename($site,$date,$create_dir=false,$date_is_timestamp=false)
	{
		//Files are stored in [filepath]/site/month/date
		if($date_is_timestamp===true)
			$date=date('Ymd',$timestamp);

		$dir=$this->filepath.'/'.$site.'/'.substr($date,0,6);

		if($create_dir!==false && !file_exists($dir))
			mkdir($dir,0777,true);
		return $dir.'/'.$date;
	}
	function next_customid()
	{
		return $this->query($q="SELECT max(customid)+1 FROM {$this->comic}",'column');
	}

	function get($args=array('date'=>null, 'site'=>'null', 'id'=>'null', 'key'=>null)) {
		if(empty($this->queries))
			$this->prepare_queries();
		if(!empty($args['key'])) //Keyfield
			return $this->execute($this->queries['keyfield'], array($args['key']), 'assoc');
		elseif(!empty($args['date']) && !empty($args['site'])) //Date and site
			return $this->execute($this->queries['date_and_site'], array($args['date'],$args['site']), 'assoc');
	}

    /**
     * @param array $args
     */
    function add_or_update($args=array('date'=>null, 'site'=>'null', 'id'=>'null', 'key'=>null, 'category'=>null)) {

        $fields = array_filter($args);
        $comic=$this->comic;
        $keyfield=$this->comic_info[$comic]['keyfield'];
        $release = $this->get($args);

        if(empty($release)) //Not in db, insert it
        {
            $q=sprintf('INSERT INTO %s (%s) VALUES (?%s)',
                $this->comic,
                implode(', ', array_keys($fields)),
                str_repeat(',?', count($fields)-1));
            $st=$this->db->prepare($q);
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
                $q = sprintf('UPDATE %s SET %s WHERE %s', $this->comic, $sets, $where);
            }
        }

        if(!empty($q)){
            $st = $this->db->prepare($q);
            $this->db->execute($st, $values);
            //$debug_q=vsprintf(str_replace('?','%s',$q),$values);
        }
    }
}
