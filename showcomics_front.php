<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Show comics</title>
<link href="comicmanager.css" rel="stylesheet" type="text/css">
</head>

<body>
<?Php
require 'class.php';
$comicmanager=new comicmanager;
$dom=$comicmanager->dom;
$comicinfo=$comicmanager->comicinfo_get();
if(!empty($comicinfo))
{
	$div_menu=$dom->createElement_simple('div');
	$dom->createElement_simple('h1',$div_menu,false,$comicinfo['name']);

	//Date form
	$dom->createElement_simple('h2',$div_menu,false,'Show strips by date');
	$form_date=$dom->createElement_simple('form',$div_menu,array('id'=>'form_date','name'=>'form_date','action'=>'showcomics.php'));
	$dom->createElement_simple('input',$form_date,array('type'=>'hidden','name'=>'comic','value'=>$comicinfo['id']));
	$dom->createElement_simple('input',$form_date,array('type'=>'hidden','name'=>'view','value'=>'date'));
	//Site field
	$sites=$comicmanager->sites();
	$p=$dom->createElement_simple('p',$form_date);
	if(count($sites)>1)
	{
		$label=$dom->createElement_simple('label',$p,false,'Site:');
		$dom->createElement_simple('input',$p,array('type'=>'text','name'=>'site','list'=>'sites'));
		$datalist=$dom->createElement_simple('datalist',$form_date,array('id'=>'sites'));
		foreach($sites as $site)
		{
			$dom->createElement_simple('option',$datalist,array('value'=>$site));
		}
	}
	else
		$dom->createElement_simple('input',$p,array('type'=>'hidden','name'=>'site','value'=>$sites[0]));

	//Date field
	$p=$dom->createElement_simple('p',$form_date);
	$label=$dom->createElement_simple('label',$p,false,'Date:');
	$dom->createElement_simple('input',$p,array('type'=>'text','name'=>'value'));
	$label=$dom->createElement_simple('label',$p,false,'Use % as wildcard');
	$dom->createElement_simple('input',$form_date,array('type'=>'submit','value'=>'Show'));

	//Key form
	$dom->createElement_simple('h2',$div_menu,false,'Show strips by key');	
	$form_key=$dom->createElement_simple('form',$div_menu,array('id'=>'form_key','name'=>'form_key','action'=>'showcomics.php'));
	$dom->createElement_simple('input',$form_key,array('type'=>'hidden','name'=>'comic','value'=>$comicinfo['id']));	
	$dom->createElement_simple('input',$form_key,array('type'=>'hidden','name'=>'view','value'=>'singlestrip'));

	$p=$dom->createElement_simple('p',$form_key);
	if($comicinfo['keyfield']!='id')
	{
		$label=$dom->createElement_simple('label',$p,false,'ID:');
		$dom->createElement_simple('input',$p,array('type'=>'checkbox','name'=>'keyfield','value'=>'id'));
	}
	$dom->createElement_simple('input',$p,array('type'=>'text','name'=>'value'));

	$dom->createElement_simple('input',$form_key,array('type'=>'submit','value'=>'Show by key'));

	//Range form
	$dom->createElement_simple('h2',$div_menu,false,'Show strips by key range');
	//Show possible range
	$range=$comicmanager->db->query($q=sprintf('SELECT MIN(%1$s) AS min, MAX(%1$s) AS max FROM %2$s',$comicinfo['keyfield'],$comicinfo['id']),'assoc');
	$dom->createElement_simple('span',$div_menu,false,sprintf('First id: %s Last id: %s',$range['min'],$range['max']));

	$form_range=$dom->createElement_simple('form',$div_menu,array('id'=>'form_range','name'=>'form_range','action'=>'showcomics.php'));
	$dom->createElement_simple('input',$form_range,array('type'=>'hidden','name'=>'comic','value'=>$comicinfo['id']));
	$dom->createElement_simple('input',$form_range,array('type'=>'hidden','name'=>'view','value'=>'range'));

	//Keyfield dropdown
	if(count($comicinfo['possible_key_fields'])>1)
	{		
		$p=$dom->createElement_simple('p',$form_range);
		$label=$dom->createElement_simple('label',$p,false,'Key field:');
		$select=$dom->createElement_simple('select',$p,array('name'=>'keyfield'));

		foreach($comicinfo['possible_key_fields'] as $key)
		{
			$select=$dom->createElement_simple('option',$select,array('value'=>$key),$key);
			if($key===$comicinfo['keyfield'])
				$select->setAttribute('selected','selected');
		}
	}

	//From field
	$p=$dom->createElement_simple('p',$form_range);
	$label=$dom->createElement_simple('label',$p,false,'From:');
	$dom->createElement_simple('input',$p,array('type'=>'text','name'=>'from','size'=>'5'));
	//To field
	//$p=$dom->createElement_simple('p',$form_range);
	$label=$dom->createElement_simple('label',$p,false,'To:');
	$dom->createElement_simple('input',$p,array('type'=>'text','name'=>'to','size'=>'5'));

	$dom->createElement_simple('br',$form_range);
	$dom->createElement_simple('input',$form_range,array('type'=>'submit','value'=>'Show by range'));

	if(!empty($comicinfo['has_categories']))
	{
		//Categories
		$dom->createElement_simple('h2',$div_menu,false,'Category');
		$categories=$comicmanager->categories();
		$ul=$dom->createElement_simple('ul',$div_menu);
		foreach($categories as $id=>$name)
		{
			$li=$dom->createElement_simple('li',$ul);
			$dom->createElement_simple('a',$li,array('href'=>sprintf('showcomics.php?comic=%s&view=category&value=%s',$comicinfo['id'],$id)),$name);
		}
	}
		$p=$dom->createElement_simple('p',$div_menu);
		$dom->createElement_simple('a',$p,array('href'=>'?'),'Change comic');
		$dom->createElement_simple('br',$p);
		$dom->createElement_simple('a',$p,array('href'=>'index.php?comic='.$comicinfo['id']),'Main menu');
		echo $dom->saveXML($div_menu);
}
		
?>
</body>
</html>