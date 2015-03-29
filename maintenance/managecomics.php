<?php
if((isset($_GET['comic']) && isset($_GET['mode']) && isset($_GET['site']))!==true)
{
	header('Location: managecomics_front.php');
	die('comic, mode and site must be specified');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo ucfirst($_GET['comic'])." ".$_GET['mode']; ?></title>
</head>
<body>
<?php
require 'maintenance_class.php';
$comicmanager=new maintenance;

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

if((!isset($releasetype) || $releasetype=='jodal') && is_object($comicmanager->comics)) //Fetch releases from jodal
{
	$releases=$comicmanager->comics->releases($site,$datefilter);
}
elseif($releasetype=='file')
{
	if(!isset($datefilter))
		$datefilter=false;
	$releases=$comicmanager->filereleases_date($site,$datefilter);
	if($releases===false && file_exists($comicmanager->filepath.'/'.$site))
	{
		$files=scandir($comicmanager->filepath.'/'.$site);
		foreach($files as $key=>$file)
		{
			if(!is_file($comicmanager->filepath.'/'.$site.'/'.$file))
				continue;
			$releases[$key]['date']='';
			$releases[$key]['file']=$comicmanager->filepath.'/'.$site.'/'.$file;
		}
	}
	else
		$releases=$comicmanager->comics->releases($site,$datefilter);
}

$st_check=$comicmanager->db->prepare("SELECT * FROM $comic WHERE site=? AND date=?");
foreach ($releases as $release)
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
		echo 'ID: <input type="number" min="0" inputmode="numeric" pattern="[0-9]*" name="value[]">'."\n";
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
?>
</body>
</html>
