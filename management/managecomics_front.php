<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Manage comics</title>
</head>

<body>
<?Php
require '../class.php';
$comicmanager=new comicmanager;
$dom=$comicmanager->dom;

$comicinfo=$comicmanager->comicinfo_get();
if($comicinfo!==false)
{
	$form=$dom->createElement_simple('form',$dom,array('method'=>'get','action'=>'managecomics_template.php'));
	
	//Select mode
	$dom->createElement_simple('input',$form,array('type'=>'hidden','name'=>'comic','value'=>$comicinfo['id'])); //Comic id
	//Build a list of possible actions
	if($comicinfo['has_categories']==1)
		$actions['category']='Category';
	if(array_search('id',$comicinfo['possible_key_fields'])!==false)
		$actions['id']='ID';
	if(array_search('original_date',$comicinfo['possible_key_fields'])!==false)
		$actions['original_date']='Original published date';
	//More than one action, allow selection
	if(count($actions)>1)
	{	
		$div_mode=$dom->createElement_simple('div',$form,array('id'=>'mode'));
		$dom->createElement_simple('p',$div_mode,'','Select action:');
		foreach($actions as $action=>$text)
		{
			$dom->createElement_simple('input',$form,array('type'=>'radio','name'=>'mode','value'=>$action,'id'=>$action));
			$dom->createElement_simple('label',$form,array('for'=>$action),$text);
			$dom->createElement_simple('br',$form);
		}
	}
	else
	{
		$action=array_keys($actions)[0];
		$dom->createElement_simple('input',$form,array('type'=>'hidden','name'=>'mode','value'=>$action));
	}

	//Select site
	$sites=$comicmanager->sites();
	$div_sites=$dom->createElement_simple('div',$form,array('id'=>'sites'));
	if(count($sites)<2)
	{
		$dom->createElement_simple('p',$div_sites,'','Enter site slug:');
		$input=$dom->createElement_simple('input',$form,array('type'=>'text','name'=>'site'));
		if(!empty($sites))
			$input->setAttribute('value',$sites[0]);
	}
	else //Site list
	{
		$dom->createElement_simple('p',$div_sites,'','Select site:');
		foreach($sites as $site)
		{
			$dom->createElement_simple('input',$div_sites,array('type'=>'radio','name'=>'site','value'=>$site,'id'=>'site_'.$site));
			$dom->createElement_simple('label',$div_sites,array('for'=>'site_'.$site),$site);
			$dom->createElement_simple('br',$div_sites);
		}
	}

	//Date
	$p_date=$dom->createElement_simple('p',$form,'','Enter date: ');
	$dom->createElement_simple('br',$p_date);
	$dom->createElement_simple('label',$p_date,array('for'=>'month'),'Month: ');
	$dom->createElement_simple('br',$p_date);
	$dom->createElement_simple('input',$p_date,array('type'=>'text','name'=>'month','id'=>'month'));
	$dom->createElement_simple('br',$p_date);
	$dom->createElement_simple('label',$p_date,array('for'=>'year'),'Year: ');	
	$dom->createElement_simple('br',$p_date);
	$dom->createElement_simple('input',$p_date,array('type'=>'text','name'=>'year','id'=>'year'));
	
	//Source
	if(is_object($comicmanager->comics))
	{
		$p_source=$dom->createElement_simple('p',$form,'','Source: ');
		$dom->createElement_simple('br',$p_source);
		foreach(array('comics'=>'Jodal comics','file'=>'Local files') as $source_id=>$source_name) //Loop to make it easier to add more sources in the future
		{
			$dom->createElement_simple('input',$p_source,array('type'=>'radio','name'=>'source','value'=>$source_id,'id'=>'source_'.$source_id));
			$dom->createElement_simple('label',$p_source,array('for'=>'source_'.$source_id),$source_name);
			$dom->createElement_simple('br',$p_source);
		}
	}
	else
		$dom->createElement_simple('input',$form,array('type'=>'hidden','name'=>'source','value'=>'file'));

	//Submit button
	$dom->createElement_simple('input',$form,array('type'=>'submit','value'=>'Submit'));

	echo $dom->saveXML($dom->documentElement);

	echo "<p><a href=\"../showcomics_front.php?comic={$comicinfo['id']}\">Show {$comicinfo['name']}</a></p>\n";
	echo "<p><a href=\"index.php?comic={$comicinfo['id']}\">Manage {$comicinfo['name']}</a></p>\n";
}
?>
</body>
</html>