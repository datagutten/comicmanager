<?Php
require 'class_management.php';
$comicmanager=new comicmanager;
echo $comicmanager->render('add_comic.twig', array('title'=>'Add comic'));

if(isset($_POST['submit']))
{
	$comic=preg_replace('/[^a-z0-9]+/','',strtolower($_POST['comic'])); //Make a clean comic id
	if(!isset($_POST['keyfield']))
		$keyfield='id';
	else
		$keyfield=preg_replace('/[^a-z0-9_]+/','',strtolower($_POST['keyfield'])); //Make a clean keyfield

	$field_definitions=array('id'=>'INT(5)','customid'=>'INT(5)','original_date'=>'INT(11)');
	$fields=$_POST['fields'];
	if(array_search($keyfield,$fields)===false)
		$fields[]=$keyfield;
	print_r($field_definitions);
	print_r(array_keys($field_definitions));
	foreach($fields as $field)
	{
		$key=array_search($field,array_keys($field_definitions));
		if($key===false)
		{
			echo "Invalid field $field\n";
			continue;
		}

		$q=sprintf("ALTER TABLE %s ADD COLUMN %s %s DEFAULT NULL",$comic,$field,$field_definitions[$field]);
		$comicmanager->db->query($q);
		echo $q."\n";
	}

	if(isset($_POST['has_categories']))
	{
		$has_categories=1;
		$q_categories='CREATE TABLE `'.$comic.'_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `visible` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
		$comicmanager->db->query($q_categories,false);

		$comicmanager->db->query('ALTER TABLE `comicmanager`.`'.$comic.'` ADD COLUMN `category` INT(2) NULL DEFAULT NULL AFTER `id`;',false);
	}
	else
		$has_categories=0;
	
	$st_comic_info=$comicmanager->db->prepare("INSERT IGNORE INTO comic_info (id,name,keyfield,has_categories,possible_key_fields) VALUES (?,?,?,?,?)");
	$possible_key_fields=implode(',',$fields);
	if(!$st_comic_info->execute(array($comic,$_POST['name'],$keyfield,$has_categories,$possible_key_fields)))
	{
		$errorinfo=$st_comic_info->errorInfo();
		trigger_error("Error inserting comic info: {$errorinfo[2]}",E_USER_WARNING);
	}
	$q_comic="CREATE TABLE `$comic` (
  `id` varchar(11) DEFAULT NULL,
  `date` int(11) DEFAULT NULL,
  `site` varchar(45) NOT NULL,
  `uid` int(11) NOT NULL AUTO_INCREMENT,";
  
  if($keyfield=='customid')
		$q_comic.="\n  `customid` int(11) DEFAULT NULL,";

$q_comic.="\n  PRIMARY KEY (`uid`)
 );";

	if(!$st_comic=$comicmanager->db->query($q_comic))
	{
		$errorinfo=$comicmanager->db->errorInfo();
		trigger_error("Error creating comic table: {$errorinfo[2]}",E_USER_WARNING);
	}
var_dump($q_comic);
}
