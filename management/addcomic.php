<?Php
require 'class_management.php';
$comicmanager=new comicmanager;

if(isset($_POST['submit']))
{
	$comic=preg_replace('/[^a-z0-9]+/','',strtolower($_POST['comic'])); //Make a clean comic id
	$q_comic="CREATE TABLE `$comic` (
	  `date` int(11) DEFAULT NULL,
	  `site` varchar(45) NOT NULL,
	  `uid` int(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY (`uid`))";
	$comicmanager->query($q_comic);
	
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
  `id` int(2) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `visible` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
		$comicmanager->query($q_categories,null);

		$comicmanager->query("ALTER TABLE $comic ADD COLUMN category INT(2) NULL DEFAULT NULL",null);
	}
	else
		$has_categories=0;
	
	$st_comic_info=$comicmanager->db->prepare("INSERT IGNORE INTO comic_info (id,name,keyfield,has_categories,possible_key_fields) VALUES (?,?,?,?,?)");
	$possible_key_fields=implode(',',$fields);
	try {
        $st_comic_info->execute(array($comic,$_POST['name'],$keyfield,$has_categories,$possible_key_fields));
        $comicmanager->comicinfo($comic);
    }
	catch (PDOException $e)
	{
		echo $comicmanager->render('error.twig', array('error'=>'Error inserting comic info: '.$e->getMessage()));
	}
}

echo $comicmanager->render('add_comic.twig', array('title'=>'Add comic'));