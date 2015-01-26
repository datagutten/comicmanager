<?Php
class comics
{
	private $db;
	public $image_host;
	function __construct()
	{
		//chdir(dirname(realpath(__FILE__))); //Bytt til mappen scriptet ligger i så relative filbaner blir riktige
		require 'config_jodal_comics.php';
		//$this->db=new PDO('mysql:host='.$mysql['server'].';dbname=comics_prod',$mysql['user'],$mysql['password']);
		$this->db=new PDO("mysql:host=$db_host;dbname=$db_name",$db_user,$db_password);
		$this->image_host=$image_host;
	}
	function releases($site,$date) //Henting av bilder fra jodal
	{
		$st=$this->db->prepare($q="SELECT file,comics_release.pub_date AS date FROM comics_release,comics_comic,comics_image WHERE
		comics_release.id=comics_image.id AND comics_comic.id=comics_release.comic_id AND 
		comics_release.pub_date LIKE ?
		AND comics_comic.slug=?");
		$st->execute(array($date,$site)) or die(print_r($st->errorInfo()));
		//echo $q."\n";
	
		if($st->rowCount()==0)
		{
			$q=str_replace(array("pub_date LIKE ?","comics_comic.slug=?"),array("pub_date LIKE '$date'","comics_comic.slug='$site'"),$q);
			die("Ingen resultater for $q<br>\n");
		}
		foreach($st->fetchAll() as $row)
		{
			
			//print_r($row);
			$fileinfo['date']=str_replace('-','',$row['date']);
			$fileinfo['file']=$this->image_host.$row['file'];
			
			$rows[]=$fileinfo;
	
		}
		return $rows;
	}
	
	function image($comic,$date)
	{
		//2012-01-01
		$st=$this->db->query("SELECT file FROM comics_release,comics_comic,comics_image WHERE
		comics_release.id=comics_image.id AND
		comics_comic.id=comics_release.comic_id AND
		comics_release.pub_date='$date' AND
		comics_comic.slug='$comic'") or die($db->error.'/'.__LINE__);
		$row=$st->fetch(PDO::FETCH_ASSOC);
		if($st->rowCount()==0)
			return false; //Not found

		if(file_exists('/home/comics_media/'.$row['file']))
			return '/home/comics_media/'.$row['file'];
		else
			return $this->image_host.$row['file'];
		//echo "<img src=\"bilde.php?bilde=/mnt/tegneserier/comics_media/media/{$row['file']}\" />";
	}
}