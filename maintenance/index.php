<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Maintenance</title>
    <link href="/comicmanager/comicmanager.css" rel="stylesheet" type="text/css"/>
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
	if($comicinfo['has_categories']==0)
		unset($tools['propagate_categories.php'],$tools['multiple_categories.php']);
	if(count(array_intersect(array('customid','id'),$comicinfo['possible_key_fields']))!=2)
		unset($tools['id_to_customid.php']);
	if(array_search('id',$comicinfo['possible_key_fields'])===false || count($comicinfo['possible_key_fields'])==1)
		unset($tools['propagate_id.php'],$tools['traceid.php']);
	
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
	
	echo "<p><a href=\"../showcomics_front.php?comic=$comic\">Show {$comicinfo['name']}</a></p>\n";
	echo "<p><a href=\"../management/?comic={$comicinfo['id']}\">Manage {$comicinfo['name']}</a></p>\n";
}
?>
</body>
</html>