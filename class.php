<?php
class comicmanager
{
	public $db;
	public $filepath;
	public $picture_host;
	public $comics;
	public $comic; //Current comic
	public $comic_info; //Array with info about comics
	public $comic_info_db; //Array with info about comics, default value from db

	public function __construct()
	{
		error_reporting(E_ALL);
		ini_set('display_errors',1);
		require 'config_db.php';
		require 'config.php';

		if(isset($comics_site) && isset($comics_key))
		{
			require_once 'class_jodal_comics.php';
			$this->comics=new comics($comics_site,$comics_key);
		}
		if(!file_exists($filepath))
			trigger_error("Invalid image file path: $filepath",E_USER_ERROR);
		else
			$this->filepath=realpath($filepath);
		if(isset($picture_host))
			$this->picture_host=$picture_host;
		
		$this->db = new PDO("mysql:host=$db_host;dbname=$db_name",$db_user,$db_password);
	}
	public function comiclist($onlyid=false) //Get all available series
	{
		$st=$this->db->query("SELECT * FROM comic_info ORDER BY name");
		if($st===false)
		{
			$errorinfo=$this->db->errorInfo();
			trigger_error("SQL error while fetching series; $errorinfo[2]",E_USER_ERROR);
			return false;
		}
		if($onlyid)
			$series=$st->fetchAll(PDO::FETCH_COLUMN);
		else
			$series=$st->fetchAll(PDO::FETCH_ASSOC);
		return $series;
	}
	public function selectcomic() //Display links to select a comic
	{
		//$st=$this->db->query("SELECT * FROM tegneserieliste ORDER BY navn");

		$output="<h2>Select comic:</h2>\n";
		foreach($this->comiclist() as $row)
		{
			$output.="<p><a href=\"?comic={$row['id']}\">{$row['name']}</a></p>\n";
		}
		return $output;
	}
	public function categories($comic,$only_visible=false)
	{
		if(!$only_visible)
			$st_categories=$this->db->prepare($q="SELECT id,name FROM {$comic}_categories ORDER BY name ASC");
		else
			$st_categories=$this->db->prepare($q="SELECT id,name FROM {$comic}_categories WHERE visible=1 ORDER BY name ASC");
		if(!$st_categories->execute())
		{
			$errorinfo=$st_categories->errorInfo();
			trigger_error("SQL error while fetching categories: $errorinfo[2]",E_USER_WARNING);
			return false;
		}
		else
			return $st_categories->fetchAll(PDO::FETCH_KEY_PAIR);
	}
	public function comicinfo($comic,$keyfield=false) //Get information about a comic
	{
		$st_comic=$this->db->prepare("SELECT * FROM comic_info WHERE id=?");
		if(!$st_comic->execute(array($comic)))
		{
			$errorinfo=$st_comic->errorInfo();
			trigger_error("SQL error while fetching comic info: $errorinfo[2]",E_USER_ERROR);
		}
		
		$comicinfo=$st_comic->fetch(PDO::FETCH_ASSOC);
		if($comicinfo===false)
			trigger_error("Invalid comic id: $comic",E_USER_ERROR);
		
		$this->comic_info_db[$comicinfo['id']]=$comicinfo;

		if($keyfield!==false)
		{
			switch($keyfield) //Check if the keyfield is valid
			{
				case 'customid': $comicinfo['keyfield']='customid'; break;
				case 'id': $comicinfo['keyfield']='id'; break;
				default: trigger_error("Invalid key field: $keyfield",E_USER_ERROR);
			}
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
			return false;
		}
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
	public function showpicture($row,$keyfield=false,$comic=false,$noheader=false,$jodal=false)
	{
		if(!is_array($row))
		{
			trigger_error("Invalid row",E_USER_WARNING);
			return false;
		}
		if($comic===false)
			$comic=$this->comic;
		if($keyfield===false)
			$keyfield=$this->comic_info[$comic];

		if(!$noheader) //Make header
		{
			if(!empty($row['date']))
				echo $row['date'].' - ';

			$urlfields=array('comic'=>$comic,'view'=>'singlestrip','value'=>$row[$keyfield]);
			if($keyfield!=$this->comic_info_db[$comic]['keyfield']) //Check if current key field is different from the default
				$urlfields['keyfield']=$keyfield;

			if(isset($row[$keyfield]))
				echo '<a href="/comicmanager/showcomics.php?'.http_build_query($urlfields,'','&amp;').'">'.$row[$keyfield].'</a>';
			else
				echo $row['uid'];
			if(isset($row['tittel']))
				echo ' - '.$row['tittel'];
			echo ' - '.$row['site'];
			echo "<br />\n";
		}

		if(!empty($row['date'])) //Show strip by date
		{
			$comics_date=preg_replace('/([0-9]{4})([0-9]{2})([0-9]{2})/','$1-$2-$3',$row['date']); //Rewrite date for comics
			if(is_object($this->comics)) //Check if the strip is found on comics
				$image=$this->comics->release_single($row['site'],$comics_date);
			if(!isset($image) || $image===false) //Image not found on comics, try to find local file
				$image=$this->typecheck($this->filepath."/{$row['site']}/".substr($row['date'],0,6)."/{$row['date']}");
		}
		else //Show strip by id
		{
			if(!empty($row['id']))
				$image=$this->typecheck($this->filepath."/{$row['site']}/{$row['id']}");
			if(isset($row['customid']) && (!isset($image) || $image===false)) //Image not found by id, try customid
				$image=$this->typecheck($this->filepath."/{$row['site']}/custom_{$row['customid']}");
		}
		if($image===false)
			echo "No image found<br />\n";
		else
		{
			if(substr($image,0,4)!='http')
				$image="/comicmanager/image.php?file=".$image;
			echo "<img src=\"$image\" alt=\"\" style=\"max-width: 1000px;\"/><br />\n";
		}
	}
}
?>