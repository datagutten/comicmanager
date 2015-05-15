<?php
$file=__FILE__;
require 'loader.php';

if($comicinfo['keyfield']=='id')
	echo "This tool is only useful for comics using an alternate key field\n";
else
{
	$comic=$comicinfo['id'];
	$keyfield=$comicinfo['keyfield'];
	
	$st=$comicmanager->db->query("SELECT * FROM $comic WHERE id!=0 AND (customid IS NULL OR id!=customid) GROUP by id ORDER BY id");
	if($st===false)
	{
		$errorinfo=$comicmanager->db->errorInfo();
		trigger_error("SQL error: ".$errorinfo[2],E_USER_ERROR);
	}
	$st_id=$comicmanager->db->query("SELECT distinct $keyfield FROM $comic ORDER BY id"); //Get all used customids
	$customidlist=$st_id->fetchAll(PDO::FETCH_COLUMN);

	$st_strips=$comicmanager->db->query($q="SELECT id,$keyfield FROM $comic WHERE id IS NOT NULL AND $keyfield IS NOT NULL GROUP BY $keyfield"); //Get id and customid relationships
	$id_and_customid=$st_strips->fetchAll(PDO::FETCH_KEY_PAIR); //key=id, value=customid

	$rows=$st->fetchAll(PDO::FETCH_ASSOC);

	foreach($rows as $row)
	{
		if(array_search($row['id'],$customidlist)===false || //Check if the customid for the current id is free
		(empty($row[$keyfield]) && isset($id_and_customid[$row['id']]))) //Check if other strips the same id has a customid
		{
			if($row[$keyfield]!='')
				$query="UPDATE $comic SET $keyfield={$row['id']} WHERE customid={$row[$keyfield]};";
			else
				$query="UPDATE $comic SET $keyfield={$row['id']} WHERE id={$row['id']};";
			//$db->query($query) or die(print_r($db->errorInfo(),true));
			if(isset($_SERVER['HTTP_USER_AGENT']))
				echo "$query<br />\n";
			else
				echo $query."\n";
			if(isset($_GET['updatedb']))
				$comicmanager->db->query($query);
		}
	}
	echo "<p><a href=\"?comic=$comic&amp;tool=id_to_customid.php&amp;updatedb\">Update database</a></p>\n";
}