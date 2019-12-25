<?php

use datagutten\comicmanager\web;

require '../vendor/autoload.php';
class management extends web
{
	function __construct()
	{
		parent::__construct();
	}

    /**
     * Get comic strips from files
     *
     * @param string $site slug
     * @param string $filter_year
     * @param string $filter_month
     * @return array
     * @throws Exception
     */
    public function releases_file_date($site, $filter_year=null, $filter_month=null)
	{

		if(!file_exists($basepath=$this->file_path.'/'.$site))
		    throw new Exception('No folder for site '.$site);


		$dir=scandir($basepath=$this->file_path.'/'.$site); //Get months
		$dir=array_diff($dir,array('.','..','Thumbs.db'));
        $releases = array();
		foreach ($dir as $month)
		{
			if(!is_dir($basepath.'/'.$month))
            {
                $this->error=sprintf('Month folder %s/%s is not a directory', $basepath, $month);
                continue;
            }

			if($filter_year!==false && substr($month,0,4)!=$filter_year) //Filter by year
				continue;
			if($filter_month!==false && substr($month,4,2)!=$filter_month) //Filter by month
				continue;
			
			$monthdir=scandir($basepath.'/'.$month); //Get the files for this month

			foreach ($monthdir as $file)
			{
				if(preg_match('^[0-9]+^',$file,$date)) //Get date from file name
				{
					$fileinfo['date']=$date[0];
					$fileinfo['file']=$basepath.'/'.$month.'/'.$file;
                    $releases[]=$fileinfo;
				}
			}
		}

        return $releases;
	}
}