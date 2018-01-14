<?php
if((isset($_GET['comic']) && isset($_GET['mode']) && isset($_GET['site']) && isset($_GET['source']))!==true)
{
	header('Location: managecomics_front.php');
	die('comic, mode, site and source must be specified');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo ucfirst($_GET['comic'])." ".$_GET['mode']; ?></title>
<script type="text/javascript">
function change_to_text(id)
{
	input=document.getElementById('input'+id);
	value=input.value;
	input.removeAttribute('inputmode');
	input.removeAttribute('pattern');
	input.setAttribute('type','text');
	input.value=value;

	changelink=document.getElementById('changelink'+id);
	changelink.setAttribute('style','display: none;');
}
</script>
</head>
<?php
require 'class_management.php';
$comicmanager=new management;
$dom=$comicmanager->dom;

$body=$dom->createElement_simple('body');

$comicinfo=$comicmanager->comicinfo_get();
if($comicinfo===false)
{
	unset($comicinfo);
	$error_text=$comicmanager->error;
}

$resort=false;

switch($_GET['mode'])
{
	case 'id': $sortmode='id'; break;
	case 'category': $sortmode='category'; break;
	case 'original_date': $sortmode='original_date'; break;
	default: trigger_error('Invalid mode: '.$_GET['mode'],E_USER_ERROR);
}

$site=$_GET['site'];

if(isset($_GET['datefilter']) && preg_match('/[0-9\-%]+/',$_GET['datefilter']))
	$datefilter=$_GET['datefilter'];
else
	$datefilter='%';

if(isset($_POST['button'])) //Form is submitted
{
	$st_indb=$comicmanager->db->prepare(sprintf('SELECT * FROM %s WHERE date=? AND site=?',$comicinfo['id']));
	foreach($_POST['value'] as $key=>$value)
	{
		$value=trim($value);
		if (!is_numeric($date=$_POST['date'][$key]) || empty($value)) //Check if the date is numeric and that the value is not empty
			continue;

		//Check if the strip is in the database
		$comicmanager->db->execute($st_indb,array($date,$site));

		if($st_indb->rowCount()==0) //Not in db, insert it
			$st_write=$comicmanager->db->prepare($q=sprintf('INSERT INTO %s (%s,date,site) VALUES (?,?,?)',$comicinfo['id'],$sortmode));
		else //Update the strip with new value
			$st_write=$comicmanager->db->prepare($q=sprintf('UPDATE %s SET %s=? WHERE date=? AND site=?',$comicinfo['id'],$sortmode));

		$debug_q=sprintf(str_replace('?','%s',$q),$value,$_POST['date'][$key],$site);
		$dom->createElement_simple('pre',$body,array('class'=>'query'),$debug_q);
		$comicmanager->db->execute($st_write,array($value,$_POST['date'][$key],$site));
	}
}

//Filter by year and/or month
if(empty($_GET['year']))
	$filter_year=false;
else
	$filter_year=$_GET['year'];
if(empty($_GET['month']))
	$filter_month=false;
else
	$filter_month=$_GET['month'];

if(!empty($comicinfo))
{
if($_GET['source']=='jodal' && is_object($comicmanager->comics)) //Fetch releases from jodal
{
	$dom->createElement_simple('p',$body,false,sprintf('Fetching releases from %s',$comicmanager->comics->site));
	if(!empty($_GET['year']) && empty($_GET['month']))
		$releases=$comicmanager->comics->releases_year($site,$_GET['year']);
	elseif(!empty($_GET['year']) && !empty($_GET['month']))
		$releases=$comicmanager->comics->releases_month($site,$_GET['year'],$_GET['month']);
	else
		$error_text='Year and/or month must be specified'; //Filtering is required when using jodal comics

	if($releases===false)
		$error_text=$comicmanager->comics->error;
}
elseif($_GET['source']=='file')
{	
	$dom->createElement_simple('p',$body,false,'Fetching releases from local files');
	$releases=$comicmanager->filereleases_date($site,$filter_year,$filter_month);

	if($releases===false)
		$error_text='No file releases found';
}
else
	$error_text=sprintf('Invalid source: %s',$_GET['source']);
}
if(empty($releases))
{
	$dom->createElement_simple('p',$body,array('class'=>'error'),'Error: '.$error_text);
	if(!empty($comicinfo))
		$dom->createElement_simple('a',$body,array('href'=>'managecomics_front.php?comic='.$comicinfo['id']),'Go back and try other options');
	else
		$dom->createElement_simple('a',$body,array('href'=>'managecomics_front.php'),'Select other comic');
}
else
{
	$st_check=$comicmanager->db->prepare(sprintf('SELECT * FROM %s WHERE site=? AND date=?',$comicinfo['id']));
	$i=1;
	$form=$dom->createElement_simple('form',$body,array('method'=>'post'));
	$dom->createElement_simple('input',$form,array('type'=>'submit','name'=>'button','value'=>'Submit'));
foreach ($releases as $key_release=>$release)
{
	$file=$release['file'];
	//$date=$release['date'];
	//echo $date."<br>\n";
	//echo $file."<br>\n";

	if(!empty($release['date']))
	{
		//Check if the strip already has a value in the selected field
		$row_check=$comicmanager->db->execute($st_check,array($site,$release['date']),'assoc');
	}
	if(empty($resort) && !empty($row_check[$sortmode]))
		continue;
	elseif($_GET['mode']=='category' && $row_check['category']!=$resort) //Resort category
		continue;
	
		$div_release=$dom->createElement_simple('div',$form);
	if(file_exists($site.'/titles/'.$release['date'].'.txt')) //Check if the strip got a title
	{
		$release_title=file_get_contents($site.'/titles/'.$release['date'].'.txt');
		$dom->createElement_simple('span',$div_release,array('class'=>'title'),$release_title);
	}
	$dom->createElement_simple('p',$div_release,false,sprintf('%s - %s',$release['date'],$site));
	$img=$dom->createElement_simple('img',$div_release,array('alt'=>$release['file']));
	if(substr($file,0,4)!=='http') //If the file is a local file, show it using "proxy script"
		$img->setAttribute('src','../image.php?file='.$release['file']);
	else //If the file is remote, show it directly
		$img->setAttribute('src',$release['file']);
	if($_GET['mode']=='category')
		$img->setAttribute('style','max-width: 1000px; max-height: 400px');
	else
		$img->setAttribute('style','max-width: 100%');

		$dom->createElement_simple('input',$div_release,array('name'=>'date[]','type'=>'hidden','value'=>$release['date']));
		$dom->createElement_simple('br',$div_release);
	if($sortmode=='id' || $sortmode==='original_date') //Id input
	{
			$dom->createElement_simple('span',$div_release,false,'ID: ');
			$dom->createElement_simple('input',$div_release,array('type'=>'number','id'=>'input'.$key_release,'inputmode'=>'numeric','pattern'=>'[0-9]*','name'=>'value[]'));
	}
	elseif($_GET['mode']=='category') //Show category select
	{
			$dom->createElement_simple('span',$div_release,false,'Category: ');
			$comicmanager->categoryselect('value[]',$div_release,false,true);
	}
	if($i>20)
		break;
	else
		$i++;
}
$dom->createElement_simple('input',$form,array('type'=>'submit','name'=>'button','value'=>'Submit'));


}
$p=$dom->createElement_simple('p',$body);
if(!empty($comicinfo))
{
	$dom->createElement_simple('a',$p,array('href'=>'../showcomics_front.php?comic='.$comicinfo['id']),'Show '.$comicinfo['name']);
	$dom->createElement_simple('br',$p);
	$dom->createElement_simple('a',$p,array('href'=>'index.php?comic='.$comicinfo['id']),'Manage '.$comicinfo['name']);
}
echo $dom->saveXML($body);
?>
</html>
