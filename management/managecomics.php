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
<body>
<?php
require 'class_management.php';
$comicmanager=new management;

$comicinfo=$comicmanager->comicinfo_get();
if($comicinfo===false)
	die();

$comic=$comicinfo['id'];


switch($_GET['mode'])
{
	case 'id': $sortmode='id'; break;
	case 'category': $sortmode='category'; break;
	trigger_error("Invalid mode: $mode",E_USER_ERROR);
}

$site=$_GET['site'];

if(isset($_GET['datefilter']) && preg_match('/[0-9\-%]+/',$_GET['datefilter']))
	$datefilter=$_GET['datefilter'];
else
	$datefilter='%';

if(isset($_GET['releasetype']))
{
	switch($_GET['releasetype'])
	{
		case 'file': $releasetype='file'; break;
		case 'jodal': $releasetype='jodal'; break;
		trigger_error("Invalid release type: {$_GET['releasetype']}",E_USER_ERROR);
	}
}
else
	$releasetype='file';

if(isset($_POST['button'])) //Form is submitted
{
	$st_indb=$comicmanager->db->prepare("SELECT * FROM $comic WHERE date=? AND site=?");
	foreach($_POST['value'] as $key=>$value)
	{
		$value=trim($value);
		if (!is_numeric($date=$_POST['date'][$key]) || empty($value)) //Check if the date is numeric and that the value is not empty
			continue;
	
		//Check if the strip is in the database
		$st_indb->execute(array($date,$site));

		if($st_indb->rowCount()==0) //Not in db, insert it
		{
			$st_write=$comicmanager->db->prepare($q="INSERT INTO $comic ($sortmode,date,site) VALUES (?,?,?)");
			echo str_replace('?,?,?',implode(',',array($value,$_POST['date'][$key],$site)),$q)."<br />\n";
		}
		else //Update the strip with new value
		{
			$st_write=$comicmanager->db->prepare($q="UPDATE $comic SET $sortmode=? WHERE date=? AND site=?");
			echo "UPDATE $comic SET $sortmode=$value WHERE date={$_POST['date'][$key]} AND site=$site<br />\n";
		}

		if(!$st_write->execute(array($value,$_POST['date'][$key],$site)))
		{
			$errorinfo=$st_categories->errorInfo();
			trigger_error("SQL error: $errorinfo[2]",E_USER_WARNING);
		}
	
	}

}

$i=1;
echo '<form id="form1" name="form1" method="post" action="">';
echo '<input type="submit" name="button" id="button" value="Submit" />';
if($_GET['mode']=='category') //Get categories
	$categories=$comicmanager->categories($comicinfo['id'],true);

//Filter by year and/or month
if(empty($_GET['year']))
	$filter_year=false;
else
	$filter_year=$_GET['year'];
if(empty($_GET['month']))
	$filter_month=false;
else
	$filter_month=$_GET['month'];

if($_GET['source']=='jodal' && is_object($comicmanager->comics)) //Fetch releases from jodal
{
	if(!empty($_GET['year']) && empty($_GET['month']))
		$releases=$comicmanager->comics->releases_year($site,$_GET['year']);
	elseif(!empty($_GET['year']) && !empty($_GET['month']))
		$releases=$comicmanager->comics->releases_month($site,$_GET['year'],$_GET['month']);
	else
		trigger_error("Year and/or month must be specified",E_USER_ERROR); //Filtering is required when using jodal comics
}
elseif($_GET['source']=='file')
{	
	$releases=$comicmanager->filereleases_date($site,$filter_year,$filter_month);

	if($releases===false)
		trigger_error("No file releases found",E_USER_ERROR);
}
else
	trigger_error("Invalid source: {$_GET['source']}",E_USER_ERROR);
if(empty($releases))
	echo "<p>No releases found.<br /><a href=\"managecomics_front.php?comic={$_GET['comic']}\">Go back and try other options</a></p>\n";
else
{
$st_check=$comicmanager->db->prepare("SELECT * FROM $comic WHERE site=? AND date=?");
foreach ($releases as $key_release=>$release)
{
	$file=$release['file'];
	//$date=$release['date'];
	//echo $date."<br>\n";
	//echo $file."<br>\n";

	if(!empty($release['date']))
	{
		if(!$st_check->execute(array($site,$release['date'])))
		{
			$errorinfo=$st_categories->errorInfo();
			trigger_error("SQL error: $errorinfo[2]",E_USER_ERROR);
		}
		$row_check=$st_check->fetch(PDO::FETCH_ASSOC);
	}
	if(!isset($resort))
	{
		if($_GET['mode']=='category' && !empty($row_check['category']))
			continue;
		elseif($_GET['mode']=='id' && !empty($row_check['id']))
			continue;
	}
	elseif($_GET['mode']=='category' && $row_check['category']!=$resort) //Resort category
		continue;
	
			
	if(file_exists($site.'/titles/'.$release['date'].'.txt')) //Check if the strip got a title
		echo file_get_contents($site.'/titles/'.$release['date'].'.txt');
	echo "<p>{$release['date']} - $site</p>";
	if(substr($file,0,4)!=='http') //If the file is a local file, show it using "proxy script"
		echo "<p><img src=\"../image.php?file={$release['file']}\" alt=\"{$release['file']}\" width=\"500\" /></p>\n";
	else //If the file is remote, show it directly
		echo "<p><img src=\"{$release['file']}\" alt=\"{$release['file']}\"/></p>\n";
	echo '<input name="date[]" type="hidden" value="'.$release['date'].'" />'."\n";

	if($_GET['mode']=='id') //Id input
		echo 'ID: <input type="number" id="input'.$key_release.'" min="0" inputmode="numeric" pattern="[0-9]*" name="value[]">'.
		'<span id="changelink'.$key_release.'" onClick="change_to_text(\''.$key_release.'\')">Change to text</span>'.
		"\n";
	elseif($_GET['mode']=='category') //Show category select
	{
		echo 'Category:<br />';
		echo '<select name="value[]">'."\n";
		echo "\t<option value=\"\">Select category</option>\n";
		foreach ($categories as $key=>$name)
		{
			echo "\t<option value=\"$key\">".htmlentities($name).'</option>'."\n";
		}
		echo '</select>'."\n";
							
	}
	if($i>20)
		break;
	else
		$i++;
}

echo '<input type="submit" name="button" id="button2" value="Submit" /></form>';
echo "\n";
}
echo "<p><a href=\"../showcomics_front.php?comic=$comic\">Show {$comicinfo['name']}</a></p>\n";
echo "<p><a href=\"index.php?comic={$comicinfo['id']}\">Manage {$comicinfo['name']}</a></p>\n";
?>
</body>
</html>
