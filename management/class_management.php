<?php
require '../class.php';
class management extends comicmanager
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

		if(!file_exists($basepath=$this->filepath.'/'.$site))
		    throw new Exception('No folder for site '.$site);


		$dir=scandir($basepath=$this->filepath.'/'.$site); //Get months
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

    /**
     * Build a select list with categories
     * @param string $name Name of the select object
     * @param $parent Parent object which the select should be appended to
     * @param bool $preselect Category to be preselected
     * @param bool $only_visible Show only categories marked as visible
     */
	public function categoryselect($name='category',$parent,$preselect=false,$only_visible=false)
	{
	    //TODO: Remove or rewrite with twig
		//Category select
		$select=$this->dom->createElement_simple('select',$parent,array('name'=>$name));
		$option_default=$this->dom->createElement_simple('option',$select,array('value'=>''),'Select category');
		if($preselect===false)
			$option_default->setAttribute('selected','selected');

		foreach ($this->categories($only_visible) as $category_id=>$category_name)
		{
			$option=$this->dom->createElement_simple('option',$select,array('value'=>$category_id),$category_name);
			if($preselect!==false && $category_id==$preselect)
				$option->setAttribute('selected','selected');
		}	
	}
}