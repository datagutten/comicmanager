<?Php
if(isset($argv)) //CLI
{
	if(!isset($comicinfo))
	{
		require '../class.php';
		$comicmanager=new comicmanager;
		if(!isset($argv[1]))
			die("usage: php ".basename($file)." [comic id]\n");
		$comicinfo=$comicmanager->comicinfo($argv[1]);
	}
}
elseif(!isset($comicinfo)) //Web
	header('Location: index.php'); //Must be used via wrapper on web