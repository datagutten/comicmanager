<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Edit categories</title>
<style type="text/css">
table {
    border-collapse: collapse;
}
table, th, td {
    border: 1px solid black;
}
</style>

</head>

<body onLoad="addfield()">
<script type="text/javascript">
let newcount=1;

function addfield() //Create a new table row with an empty field
{
    const table=document.getElementsByTagName('table');
    const tr=document.createElement('tr');

    const td_name=document.createElement('td');
    const input_name=document.createElement('input');
	input_name.setAttribute('type','text'); //type="text"
	input_name.setAttribute('name',"categories[new"+newcount+"]");
	input_name.setAttribute('onchange','addfield()');
	td_name.appendChild(input_name); //Add the input to the td
	tr.appendChild(td_name); //Add the td to the tr

    const td_delete=document.createElement('td');
	tr.appendChild(td_delete); //Empty td, no need for delete button for new category

    const td_visible=document.createElement('td');
    const input_visible=document.createElement('input');
	input_visible.setAttribute('type','checkbox');
	input_visible.setAttribute('name',"visible[new"+newcount+"]");
	input_visible.setAttribute('checked','checked');
	td_visible.appendChild(input_visible); //Add the input to the td
	tr.appendChild(td_visible); //Add the td to the tr

	table.item(0).appendChild(tr); //Add the tr to the table

	newcount++;
}
</script>
<form method="post">
<?Php
require '../vendor/autoload.php';

$comicmanager=new comicmanager;
$comicinfo=$comicmanager->comicinfo_get();
$dom=$comicmanager->dom;

if(is_array($comicinfo) && $comicinfo['has_categories']==1)
{
	$table=$comicinfo['id']."_categories";
	$st_categories=$comicmanager->db->query($q="SELECT * FROM $table ORDER BY name");
	$st_visible_update=$comicmanager->db->prepare("UPDATE $table SET visible=? WHERE id=?");
	$st_insert=$comicmanager->db->prepare("INSERT INTO $table (name,visible) VALUES (?,?)");
	$st_delete=$comicmanager->db->prepare("DELETE FROM $table WHERE id=?");
	$categories_db_all=$st_categories->fetchAll(PDO::FETCH_ASSOC);

	$categories_db=array_column($categories_db_all,'name','id');
	$visible_db=array_column($categories_db_all,'visible','id');

	if(isset($_POST['submit']))
	{
		$st_update_name=$comicmanager->db->prepare("UPDATE $table SET name=? WHERE id=?");

		foreach($_POST['categories'] as $id=>$name)
		{
			if(empty($name))
				continue;
			if(!isset($categories_db[$id])) //New category
			{
				echo "INSERT INTO $table (name,visible) VALUES ($name,1)<br />\n";
				$comicmanager->db->execute($st_insert,array($name,1));
				$id=$comicmanager->db->lastInsertId();
				$categories_db[$id]=$name;
				$visible_db[$id]=1;
				continue;
			}
			elseif($name!=$categories_db[$id]) //Check if name is changed
			{
				echo "UPDATE $table SET name=$name WHERE id=$id<br />\n";
				$comicmanager->db->execute($st_update_name,array($name,$id));
				$categories_db[$id]=$name; //Update the name variable to avoid reloading from db
			}
			if(!isset($_POST['visible'][$id])) //Box not checked means not visible
				$_POST['visible'][$id]=0;
			if(is_numeric($_POST['visible'][$id]) && $_POST['visible'][$id]!=$visible_db[$id]) //Check if visible status is changed
			{
				echo "UPDATE $table SET visible={$_POST['visible'][$id]} WHERE id=$id<br />\n"; //Dummy query for troubleshooting
				$comicmanager->db->execute($st_visible_update,array($_POST['visible'][$id],$id));
				$visible_db[$id]=$_POST['visible'][$id];
			}
			if(isset($_POST['delete'][$id]))
			{
				echo "DELETE FROM $table WHERE id=$id<br />\n";
				$comicmanager->db->execute($st_delete,array($id));
				unset($categories_db[$id]);
			}

		}
		$visible=$_POST['visible'];
	}
	//Create table
	$table=$dom->createElement_simple('table',$dom);
	//$table=$dom->createElement('table');
	//$table->setAttribute('border','1');
	//Header row
	$tr=$dom->createElement('tr');
	$th_name=$dom->createElement('th','Name');
	$tr->appendChild($th_name);
	$th_delete=$dom->createElement('th','Delete');
	$tr->appendChild($th_delete);
	$th_visible=$dom->createElement('th','Visible');
	$tr->appendChild($th_visible);
	
	$table->appendChild($tr);
	
	$dom->appendChild($table);
	foreach($categories_db as $id=>$name)
	{
		$tr=$dom->createElement('tr'); //Create row
		
		//Category name
		$td_name=$dom->createElement('td');
		$input_name=$dom->createElement('input');
		$input_name->setAttribute('type','text'); //type="text"
		$input_name->setAttribute('name',"categories[$id]");
		$input_name->setAttribute('value',$name); //Category name
		$td_name->appendChild($input_name); //Add the input to the column
		$tr->appendChild($td_name); //Add the column to the the row

		//Delete checkbox
		$td_delete=$dom->createElement('td');
		$input_delete=$dom->createElement('input');
		$input_delete->setAttribute('type','checkbox');
		$input_delete->setAttribute('name',"delete[$id]");
		$td_delete->appendChild($input_delete); //Add the input to the column
		$tr->appendChild($td_delete); //Add the column to the the row

		//Visible checkbox
		$td_visible=$dom->createElement('td');
		$input_visible=$dom->createElement_simple('input',$td_visible,array('type'=>'checkbox','name'=>"visible[$id]",'value'=>'1'));
		/*$input_visible=$input_delete->cloneNode(true);
		$input_visible->setAttribute('name',"visible[$id]");*/
		if($visible_db[$id])
			$input_visible->setAttribute('checked','checked');
		//$td_visible->appendChild($input_visible);
		$tr->appendChild($td_visible); //Add the column to the the row

		$table->appendChild($tr); //Add the row to the table
	}
	echo $dom->saveXML($dom->documentElement);
	echo '<input name="submit" type="submit">';
}
elseif($comicinfo['has_categories']==0)
	echo $comicinfo['name'].' does not have categories';
?>
</form>
</body>
</html>