<?php
$file=__FILE__;
require 'loader.php';

if($comicinfo!==false)
{
	if($comicinfo['keyfield']=='id')
		echo "This tool is only useful for comics using an alternate key field";
	else
	{
		$comic=$comicinfo['id'];
		$keyfield=$comicinfo['keyfield'];
		$st_update=$comicmanager->db->prepare("UPDATE $comic SET id=? WHERE uid=?");
	
		$q="SELECT customid,id FROM $comic WHERE id IS NOT NULL AND $keyfield IS NOT NULL GROUP BY $keyfield"; 
		$st_strips=$comicmanager->db->query($q); //Get all unique strips with category
		/*if(!is_object($st_strips)); 
		{
			$errorinfo=$comicmanager->db->errorInfo();
			trigger_error("Error fetching strips: {$errorinfo[2]}",E_USER_ERROR);
		}*/
		$ids=$st_strips->fetchAll(PDO::FETCH_KEY_PAIR); //key=customid, value=id
		//print_r($ids);
		$st_missing=$comicmanager->db->query("SELECT * FROM $comic WHERE $keyfield IS NOT NULL AND id IS NULL");
		
		foreach($st_missing->fetchAll(PDO::FETCH_ASSOC) as $strip)
		{
			if(isset($ids[$strip[$keyfield]]))
			{
				$key=$strip[$keyfield];
				echo "UPDATE $comic SET id={$ids[$key]} WHERE uid={$strip['uid']}<br />\n";
				$st_update->execute(array($ids[$key],$strip['uid']));
			}
		}
	}
}
?>