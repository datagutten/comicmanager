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

require '../tools/DOMDocument_createElement_simple.php';
$dom=new DOMDocumentCustom;
$dom->formatOutput=true;

$comicinfo=$comicmanager->comicinfo_get();
if($comicinfo!==false)
{
	$form=$dom->createElement_simple('form',$dom,array('method'=>'get','action'=>'managecomics.php'));
	
	//Select mode
	if(!$st_sites=$comicmanager->db->query("SELECT site FROM {$comicinfo['id']} GROUP BY site"))
	{
		$errorinfo=$comicmanager->db->errorInfo();
		trigger_error("SQL error fetching sites: $errorinfo[2]",E_USER_ERROR);
	}

	$dom->createElement_simple('input',$form,array('type'=>'hidden','name'=>'comic','value'=>$comicinfo['id'])); //Comic id
	if($comicinfo['has_categories']==0)
		$dom->createElement_simple('input',$form,array('type'=>'hidden','name'=>'mode','value'=>'id')); //No categories, mode is always id
	else
	{
		$div_mode=$dom->createElement_simple('div',$form,array('id'=>'mode'));
		$dom->createElement_simple('p',$div_mode,'','Select action:');
		//Category
		$dom->createElement_simple('input',$form,array('type'=>'radio','name'=>'mode','value'=>'category','id'=>'category'));
		$dom->createElement_simple('label',$form,array('for'=>'category'),'Category');
		$dom->createElement_simple('br',$form);
		//ID
		$dom->createElement_simple('input',$form,array('type'=>'radio','name'=>'mode','value'=>'id','id'=>'id'));
		$dom->createElement_simple('label',$form,array('for'=>'id'),'ID');
		$dom->createElement_simple('br',$form);
	}
	

	//Select site
	$sites=$st_sites->fetchAll(PDO::FETCH_COLUMN);
	if(count($sites)==1)
		$dom->createElement_simple('input',$form,array('type'=>'hidden','name'=>'site','value'=>$sites[0])); //Only one site
	else //Site list
	{
		$div_sites=$dom->createElement_simple('div',$form,array('id'=>'sites'));
		$dom->createElement_simple('p',$div_sites,'','Select site:');
		foreach($sites as $site)
		{
			$dom->createElement_simple('input',$div_sites,array('type'=>'radio','name'=>'site','value'=>$site,'id'=>'site_'.$site));
			$dom->createElement_simple('label',$div_sites,array('for'=>'site_'.$site),$site);
			$dom->createElement_simple('br',$div_sites);
		}
	}
	$p_datefilter=$dom->createElement_simple('p',$form,'','Date filter: ');
	$dom->createElement_simple('input',$p_datefilter,array('type'=>'text','name'=>'datefilter'));

	$dom->createElement_simple('input',$form,array('type'=>'submit','value'=>'Submit'));

	echo $dom->saveXML($dom->documentElement);
}
?>
</body>
</html>