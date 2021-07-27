<?Php
/** @noinspection PhpUndefinedVariableInspection */
$file=__FILE__;
require 'loader.php';

$valid_categories=$comicmanager->info->categories(); //Get valid categories
$comic=$comicinfo['id'];
$keyfield=$comicinfo['keyfield'];

$st_categories=$comicmanager->db->query("SELECT $keyfield,category FROM $comic WHERE category IS NOT NULL AND $keyfield IS NOT NULL");
$st_strip=$comicmanager->db->prepare("SELECT * FROM $comic WHERE $keyfield=?");
foreach($st_categories->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_GROUP) as $strip=>$categories)
{
	if(count(array_unique($categories))>1)
	{
		if(isset($argv)) //CLI
			echo "Multiple categories for $keyfield $strip:\n";
		else
        {
            $parameters = array('comic'=>$comic, 'view'=>'singlestrip', 'key_field'=>$keyfield, 'key_from'=>$strip);
            $parameters = http_build_query($parameters);
            echo "Multiple categories for <a href=\"../showcomics.php?$parameters\">$keyfield $strip:</a> (<a href=\"../management/edit_release.php?comic=$comic&amp;keyfield={$comicinfo['keyfield']}&amp;key=$strip\">Edit</a>)<br />\n";
        }


		$st_strip->execute(array($strip));
		foreach($st_strip->fetchAll(PDO::FETCH_ASSOC) as $strip_release)
		{
			if(isset($valid_categories[$strip_release['category']]))
				$categoryname=$valid_categories[$strip_release['category']];
			else
				$categoryname="invalid";
			if(isset($argv)) //CLI
				echo "\tuid {$strip_release['uid']} got category {$strip_release['category']} ($categoryname)\n";
			else
				echo "&nbsp;&nbsp;uid {$strip_release['uid']} got category {$strip_release['category']} ($categoryname)<br />\n";
		}
	}
}
