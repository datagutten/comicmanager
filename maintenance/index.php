<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Maintenance</title>
</head>

<body>
<h2>Tools for maintaining the database</h2>
<?Php
require '../class.php';
$comicmanager=new comicmanager;
$tools=array('id_to_customid.php'=>'Set id as customid','propagate_categories.php'=>"Propagate category to all copies of a strip",'multiple_categories.php'=>'Find strips with multiple categories','propagate_id.php'=>"Propagate id to all copies of a strip",'traceid.php'=>'Trace ID','comparemonths.php'=>'Compare months');

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
	}
	elseif(isset($tools[$_GET['tool']]))
	{
		echo "<h3>{$_GET['tool']}</h3>\n";
		require $_GET['tool'];
		echo "<p><a href=\"?comic=$comic\">Back to tool selection</a></p>\n";
	}
	else
		echo "Invalid tool: {$_GET['tool']}";
	
}
?>
</body>
</html>