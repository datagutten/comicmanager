<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Add id files</title>
</head>

<body>
<?Php
require '../vendor/autoload.php';
$comicmanager=new comicmanager;
$comicinfo=$comicmanager->comicinfo_get();
if(is_array($comicinfo))
{
	if(!isset($_GET['site'])) //Select site
	{
		echo "<h2>Select site:</h2>\n";
		foreach(scandir($comicmanager->filepath) as $site)
		{
			if(is_dir($comicmanager->filepath."/$site") && substr($site,0,strlen($comicinfo['id']))==$comicinfo['id']) //Show sites for selected comic
			{
				echo "<a href=\"?comic={$comicinfo['id']}&amp;site=$site\">$site</a><br />\n";
			}
		}
	}
	else //Site and comic selected, show strips
	{
		$site=$_GET['site'];
		echo "<form action=\"\" method=\"post\">\n";
		$st_insert=$comicmanager->db->prepare("INSERT INTO {$comicinfo['id']} (id,site) VALUES (?,?)");
		$st_select=$comicmanager->db->prepare("SELECT * FROM {$comicinfo['id']} WHERE id=? AND site=?");
		echo "<strong>Strips to be added</strong><br />\n";
		foreach(scandir($dir_site=$comicmanager->filepath."/{$_GET['site']}") as $file)
		{
			if(is_file($dir_site.'/'.$file) && preg_match('/([0-9]+)/',$file,$matches))
			{
				$stripstring="{$matches[1]} $site";
				//Check if the strip is already in the database
				$comicmanager->db->execute($st_select,array($matches[1],$site));
				if($st_select->rowCount()>0) //Already in db
					continue;
				
				if(!isset($_POST['submit']))
				{
					echo "<strong>$stripstring</strong><br />\n";
					echo "<img src=\"../image.php?file=$dir_site/$file\" width=\"500\"/><br />\n";
					//echo "<input type=\"checkbox\" name=\"add[$file]\">Add $stripstring<br />\n";
				}
				else
				{
					//Insert the strip in the DB
					$comicmanager->db->execute($st_insert,array($matches[1],$site));
					echo "<p>Inserted $stripstring</p>\n";
				}
			}
		}
		echo '<input name="submit" type="submit">'."\n";
		echo '</form>'."\n";
	}
	echo "<p><a href=\"index.php?comic={$comicinfo['id']}\">Manage {$comicinfo['name']}</a></p>\n";
	echo "<p><a href=\"../showcomics.php?comic={$comicinfo['id']}\">Show {$comicinfo['name']}</a></p>\n";

}
?>
</body>
</html>