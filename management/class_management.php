<?php
require '../class.php';
class management extends comicmanager
{
	public $dom;
	function __construct()
	{
		parent::__construct();
		require_once '../tools/DOMDocument_createElement_simple.php';
		$this->dom=new DOMDocumentCustom;
	}
	public function filereleases_date($site,$filter_year=false,$filter_month=false) //Used by managecomics
	{
		if(!file_exists($basepath=$this->filepath.'/'.$site))
			return false;
		$dir=scandir($basepath=$this->filepath.'/'.$site); //Get months
		$dir=array_diff($dir,array('.','..','Thumbs.db'));

		foreach ($dir as $month)
		{
			if(!is_dir($basepath.'/'.$month))
				return false; //No month folder

			if($filter_year!==false && substr($month,0,4)!=$filter_year) //Filter by year
				continue;
			if($filter_month!==false && substr($month,2,2)!=$filter_month) //Filter by month
				continue;
			
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
	public function categoryselect($name='category',$parent,$preselect=false)
	{
		//Category select
		$select=$this->dom->createElement_simple('select',$parent,array('name'=>'category'));
		if($preselect==false)
			$option_default=$this->dom->createElement_simple('option',$select,array('selected'=>'selected'),"Select category");
		foreach ($this->categories($this->comic) as $category_id=>$category_name)
		{
			$option=$this->dom->createElement_simple('option',$select,array('value'=>$category_id),$category_name);
			if($preselect!==false && $category_id==$preselect)
				$option->setAttribute('selected','selected');		
		}	
	}
}