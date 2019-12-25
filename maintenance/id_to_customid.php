<?php
$file=__FILE__;
require 'loader.php';

if($comicinfo['keyfield']=='id')
	echo "This tool is only useful for comics using an alternate key field\n";
else
{
	$st_custom_id = $comicmanager->db->prepare(sprintf('SELECT DISTINCT id FROM %s WHERE customid=?', $comicinfo['id']));
	$comic=$comicinfo['id'];
	$keyfield=$comicinfo['keyfield'];
	$st=$comicmanager->db->query("SELECT * FROM $comic WHERE id!=0 AND (customid IS NULL OR id!=customid) ORDER BY id");

	while($row=$st->fetch())
	{
		echo '<pre>'.print_r($row, true).'</pre>';
		//$st_custom_id = $comicmanager->get(array('customid'=>$row['id']), true);
		$st_custom_id->execute(array($row['id'])); //Find releases with customid similar to this release id
		$count = $st_custom_id->rowCount();
		if($count>1)
		{
			printf("Multiple ids for customid %d:<br />\n", $row['customid']);
			while($row_id = $st_custom_id->fetch())
			{
				echo $row_id[0]."<br />\n";
			}
			continue;
		}
		elseif($count==1)
		{
			$release = $st_custom_id->fetch();
			if($release['id']!=$row['id']) //Check if the matching release has the same id
			{
				printf("Release with customid %d has different id: %d<br />\n", $row['id'], $release['id']);
				continue;
			}
			else
				printf("Release with customid %d and uid %d has same id as customid %d<br />\n", $row['customid'], $row['uid'], $row['id']);
		}
		else
		{
			printf("Customid %d is free<br />\n", $row['id']);
		}

		try {
			$comicmanager->add_or_update(array('customid'=>$row['id'], 'uid'=>$row['uid']));
		}
		catch (Exception $e)
		{
			echo $e."<br />\n";
		}
	}
}