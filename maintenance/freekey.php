<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Untitled Document</title>
</head>

<body>

<?Php
require '../class.php';
$comicmanager=new comicmanager;
$comicinfo=$comicmanager->comicinfo_get();

if(is_array($comicinfo))
{
	$st_max=$comicmanager->db->query("SELECT MAX({$comicinfo['keyfield']}) FROM {$comicinfo['id']}");
	$st_list=$comicmanager->db->query($q="SELECT distinct {$comicinfo['keyfield']} FROM {$comicinfo['id']} ORDER by {$comicinfo['keyfield']}");
	$list=$st_list->fetchAll(PDO::FETCH_COLUMN);
	//print_r($list);
	//die();
	$count=0;
	$max=$st_max->fetch(PDO::FETCH_COLUMN);
	//var_dump($max);
	$key=1;
	for ($i=1; $i<=$max; $i++)
	{
		if($list[$key]!=$i)
		{
			echo $i.'<br>';
			$count++;
		}
		else
			$key++;
	}
	echo 'Total unused keys: '.$count;
}
?>
</body>
</html>