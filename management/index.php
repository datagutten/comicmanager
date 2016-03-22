<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Management</title>
</head>

<body>
<h1>Tools for editing information in the database</h1>
<?Php
require '../class.php';
$comicmanager=new comicmanager;
$tools=array('edit_categories.php'=>"Add or edit categories",'managecomics_front.php'=>'Add id or category to strips','edit_strip.php'=>'Edit strip info','add_id_files.php'=>'Add files with id as name to the database');
if(isset($_GET['tool']))
	echo "<h2>{$tools[$_GET['tool']]}</h2>\n";

$comicinfo=$comicmanager->comicinfo_get();
if($comicinfo!==false)
{
	$comic=$comicinfo['id'];

	if(!isset($_GET['tool']))
	{
		foreach($tools as $file=>$name)
		{
			echo "<a href=\"?comic=$comic&amp;tool=$file\">$name</a><br />\n";
		}
		echo "<p><a href=\"../showcomics.php?comic=$comic\">Show {$comicinfo['name']}</a></p>\n";
		echo "<p><a href=\"../maintenance/?comic={$comicinfo['id']}\">Maintain {$comicinfo['name']}</a></p>\n";
	}
	elseif(isset($tools[$_GET['tool']]))
	{
		header('Location: '.$_GET['tool'].'?comic='.$_GET['comic']);
	}
	else
		echo "Invalid tool: {$_GET['tool']}";
	
}
?>
</body>
</html>