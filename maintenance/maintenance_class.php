<?php
require '../class.php';
class maintenance extends comicmanager
{
	function __construct()
	{
		parent::__construct();
	}
	public function filereleases_date($site,$datefilter=false) //Used by managecomics
	{
		if(is_string($datefilter))
			$datefilter=str_replace('%','',$datefilter);
		if(!file_exists($basepath=$this->filepath.'/'.$site))
			return false;
		$dir=scandir($basepath=$this->filepath.'/'.$site); //Get months
		$dir=array_diff($dir,array('.','..','Thumbs.db'));

		foreach ($dir as $month)
		{
			if(!empty($datefilter) && strpos($month,$datefilter)===false)
				continue;
			if(!is_dir($basepath.'/'.$month))
				return false; //No month folder
			$monthdir=scandir($basepath.'/'.$month); //Get the files for this month

			foreach ($monthdir as $file)
			{
				if(preg_match('^[0-9]+^',$file,$date)) //Get date from file name
				{
					$fileinfo['date']=$date[0];
					$fileinfo['file']=$basepath.'/'.$month.'/'.$file;
					$rows[]=$fileinfo;
				}
			}
		}
		if(!isset($rows))
			die("No strips found<br />\n");
		return $rows;
	}

}