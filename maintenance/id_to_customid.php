<?php
$file=__FILE__;
require 'loader.php';

if($comicinfo['keyfield']!=='customid')
	echo "{$comicinfo['name']} does not use customid\n";
else
{
	$comic=$comicinfo['id'];
	
	$st=$comicmanager->db->query('SELECT * FROM pondus WHERE id!=0 AND (customid IS NULL OR id!=customid) GROUP by id ORDER BY id');
	$st_id=$comicmanager->db->query("SELECT distinct customid FROM $comic ORDER BY id");
	$customidlist=$st_id->fetchAll(PDO::FETCH_COLUMN);
	//print_r($idlist);
	$rows=$st->fetchAll(PDO::FETCH_ASSOC);

	foreach($rows as $row)
	{
		if(array_search($row['id'],$customidlist)===false) //Sjekk om customid er ledig
		{
			if($row['customid']!='')
				$query="UPDATE $comic SET customid={$row['id']} WHERE customid={$row['customid']};";
			else
				$query="UPDATE $comic SET customid={$row['id']} WHERE id={$row['id']};";
			//$db->query($query) or die(print_r($db->errorInfo(),true));
			if(isset($_SERVER['HTTP_USER_AGENT']))
				echo "$query<br />\n";
			else
				echo $query."\n";
		}
	}
}