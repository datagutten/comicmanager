<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Edit categories</title>
</head>

<body>
<script type="text/javascript">
function addfield()
{
	var newfield=document.createElement('p');
	newfield.innerHTML=document.getElementById('category_add').innerHTML;
	document.getElementById('morefields').appendChild(newfield);
}

</script>
<form action="" method="post">
<?Php
require '../class.php';
$comicmanager=new comicmanager;
$comicinfo=$comicmanager->comicinfo_get();
if(is_array($comicinfo))
{
	$table=$comicinfo['id']."_categories";
	$st_categories=$comicmanager->db->query($q="SELECT id,name FROM $table ORDER BY name ASC");
	$categories=$st_categories->fetchAll(PDO::FETCH_KEY_PAIR);

	if(isset($_POST['submit']))
	{
		$st_update=$comicmanager->db->prepare("UPDATE $table SET name=? WHERE id=?");
		foreach($_POST['categories'] as $id=>$name)
		{
			if($name!=$categories[$id])
			{
				echo "UPDATE $table SET name=$name WHERE id=$id<br />\n";
				$st_update->execute(array($name,$id));
				$categories[$id]=$name; //Correct the name to avoid reloading from db
			}
		}
	}
	
	
	foreach($categories as $id=>$name)
	{
		$name=htmlentities($name);
		echo "<p><input type=\"text\" name=\"categories[$id]\" value=\"$name\" /><input name=\"delete[$id]\" type=\"checkbox\" value=\"\"></p>\n";
	}
	echo "<p id=\"category_add\"><input type=\"text\" name=\"category_add[]\" onChange=\"addfield()\"/></p>\n";
	echo '<div id="morefields"></div>';
	echo '<input name="submit" type="submit">';
	
}
?>
</form>
</body>
</html>