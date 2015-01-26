<?php
class comicmanager
{
	public $db;
	public $filepath;
	public $picture_host;
	public $comics;
	public function __construct()
	{
		error_reporting(E_ALL);
		ini_set('display_errors',1);
		require 'config_db.php';
		require 'config.php';
		if(file_exists('config_jodal_comics.php'))
		{
			require_once 'class_jodal_comics.php';
			$this->comics=new comics;
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
		
		if($keyfield!==false)
		{
			switch($keyfield) //Check if the keyfield is valid
			{
				case 'customid': $comicinfo['keyfield']='customid'; break;
				case 'id': $comicinfo['keyfield']='id'; break;
				default: trigger_error("Invalid key field: $keyfield",E_USER_ERROR);
			}
		}
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
				$filename=$filename.".$type";
				break;
			}
		}
		if($typereturn===true)
			$filename=$type;
		return $filename;
	}
	public function showpicture($row,$keyfield,$comic=NULL,$noheader=false,$jodal=false)
	{
		if($comic==NULL && isset($_GET['comic']))
			$comic=$_GET['comic'];

		if($row['date']=='0') //Hvis filen ikke har dato, bruk id som dato
		{
			$row['date']=$row['id'];
			$folder='';
			if($row['id']=='0')
				$row['date']='custom_'.$row['customid'];
		}
		else
			$folder=substr($row['date'],0,6);
		if(!$noheader) //Make header
		{
			echo $row['date'];
			echo ' - ';
			if($keyfield=='id')
				$idlink='&amp;id=true';
			else
				$idlink='';

			$urlfields=array_merge($_GET,array('comic'=>$comic,'view'=>'singlestrip','value'=>$row[$keyfield]));

			if(!isset($_GET['key']) && isset($row[$keyfield]))
				echo '<a href="showcomics.php?'.http_build_query($urlfields,'','&amp;').'">'.$row[$keyfield].'</a>';
			else
				echo $row[$keyfield];
			if(isset($row['tittel']))
				echo ' - '.$row['tittel'];
			echo ' - '.$row['site'];
			echo "<br />\n";
		}

		if(is_object($this->comics) && ($image=$this->comics->image($row['site'],$row['date']))!==false) //Check if the strip is found on comics
			echo "<img src=\"$image\" alt=\"{$row['date']}\" /><br />\n";
		elseif(file_exists($imagefile=$this->typecheck($this->filepath."/{$row['site']}/$folder/{$row['date']}")))
			echo "<img src=\"image.php?file=$imagefile\" width=\"800\" /><br />\n";
		else //Remote picture host
			echo "<img src=\"{$this->picture_host}bilde.php?site={$row['site']}&amp;folder=$folder&amp;date={$row['date']}\" alt=\"{$row['date']}\"/><br>\n";
		echo "\n"; //Extra line break to separate strips
	}
}
?>