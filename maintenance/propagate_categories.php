<?php
$file=__FILE__;
require 'loader.php';

if($comicinfo!==false && $comicinfo['has_categories']==1)
{
	$comic=$comicinfo['id'];
	$keyfield=$comicinfo['keyfield'];
	$st_update=$comicmanager->db->prepare("UPDATE $comic SET category=? WHERE uid=?");

	$q="SELECT $keyfield,category FROM $comic WHERE category IS NOT NULL AND $keyfield IS NOT NULL GROUP BY $keyfield"; 
	$st_strips=$comicmanager->db->query($q); //Get all unique strips with category
	/*if(!is_object($st_strips)); 
	{
		$errorinfo=$comicmanager->db->errorInfo();
		trigger_error("Error fetching strips: {$errorinfo[2]}",E_USER_ERROR);
	}*/
	$categories=$st_strips->fetchAll(PDO::FETCH_KEY_PAIR);

	$st_missing=$comicmanager->db->query("SELECT * FROM $comic WHERE $keyfield IS NOT NULL AND category IS NULL");
	
	foreach($st_missing->fetchAll(PDO::FETCH_ASSOC) as $strip)
	{
		if(isset($categories[$strip[$keyfield]]))
		{
			$key=$strip[$keyfield];
			echo $key."\n";
			echo "UPDATE $comic SET category={$categories[$key]} WHERE uid={$strip['uid']}\n";
			$st_update->execute(array($categories[$strip[$keyfield]],$strip['uid']));
		}
	}
}
?>