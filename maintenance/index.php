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
$tools=array('propagate_categories.php'=>"Propagate category to all copies of a strip",'id_to_customid.php'=>"Set id as customid",'multiple_categories.php'=>'Find strips with multiple categories','propagate_id.php'=>"Propagate id to all copies of a strip");

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
		require $_GET['tool'];
	}
	else
		echo "Invalid tool: {$_GET['tool']}";
	
}
?>
</body>
</html>