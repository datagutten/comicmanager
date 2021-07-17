<?Php

use datagutten\comicmanager\comicmanager;
use datagutten\comicmanager\maintenance\Maintenance;

if(isset($argv)) //CLI
{
	if(!isset($comicinfo))
	{
        require __DIR__.'/../vendor/autoload.php';
		$comicmanager=new comicmanager;
		if(!isset($argv[1]))
			die("usage: php ".basename($file ?? '[tool name]')." [comic id]\n");
		$comicinfo=$comicmanager->comicinfo($argv[1]);
		return new Maintenance($comicmanager);
	}
}
elseif(!isset($comicinfo)) //Web
	header('Location: index.php'); //Must be used via wrapper on web