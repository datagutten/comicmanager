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
		$keyfield=preg_replace('/[^a-z0-9]+/','',strtolower($_POST['keyfield'])); //Make a clean keyfield
	if(isset($_POST['has_categories']) && $_POST['has_categories']==1)
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
	
	$st_comic_info=$comicmanager->db->prepare("INSERT INTO comic_info (id,name,keyfield,has_categories) VALUES (?,?,?,?)");
	
	if(!$st_comic_info->execute(array($comic,$_POST['name'],$keyfield,$has_categories)))
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
