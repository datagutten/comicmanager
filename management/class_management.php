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