<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Edit categories</title>
</head>

<body onLoad="addfield()">
<script type="text/javascript">
var newcount=1;

function addfield() //Create a new table row with an empty field
{
	var table=document.getElementsByTagName('table');
	var tr=document.createElement('tr');

	var td_name=document.createElement('td');
	var input_name=document.createElement('input');
	input_name.setAttribute('type','text'); //type="text"
	input_name.setAttribute('name',"categories[new"+newcount+"]");
	input_name.setAttribute('onchange','addfield()');
	td_name.appendChild(input_name); //Add the input to the td
	tr.appendChild(td_name); //Add the td to the tr

	var td_delete=document.createElement('td');
	tr.appendChild(td_delete); //Empty td, no need for delete button for new category

	var td_visible=document.createElement('td');
	var input_visible=document.createElement('input');
	input_visible.setAttribute('type','checkbox');
	input_visible.setAttribute('name',"visible[new"+newcount+"]");
	td_visible.appendChild(input_visible); //Add the input to the td
	tr.appendChild(td_visible); //Add the td to the tr

	table.item(0).appendChild(tr); //Add the tr to the table

	newcount++;
}
</script>
<form action="" method="post">
<?Php
require '../class.php';
$comicmanager=new comicmanager;
$comicinfo=$comicmanager->comicinfo_get();
$dom=new DOMDocument;
$dom->formatOutput = true;

if(is_array($comicinfo))
{
	$table=$comicinfo['id']."_categories";
	$st_categories=$comicmanager->db->query($q="SELECT * FROM $table ORDER BY name ASC");
	$st_visible_update=$comicmanager->db->prepare("UPDATE $table SET visible=? WHERE id=?");
	$st_insert=$comicmanager->db->prepare("INSERT INTO $table (name,visible) VALUES (?,?)");
	$categories_db_all=$st_categories->fetchAll(PDO::FETCH_ASSOC);

	$categories_db=array_column($categories_db_all,'name','id');
	$visible_db=array_column($categories_db_all,'visible','id');

	if(isset($_POST['submit']))
	{
		$st_update=$comicmanager->db->prepare("UPDATE $table SET name=? WHERE id=?");
		if(!isset($_POST['visible'][$id]) || !is_numeric($_POST['visible']))
			$_POST['visible'][$id]=0;

		foreach($_POST['categories'] as $id=>$name)
		{
			if(empty($name))
				continue;
			if(!isset($categories[$id])) //New category
			{
				if(!$st_insert->execute(array($name,$id)))
				{
					$errorinfo=$this->db->errorInfo();
					trigger_error("SQL error inserting category: $errorinfo[2]",E_USER_WARNING);
				}
			}
			elseif($name!=$categories_db[$id]) //Check if name from form is different than db
			{
				echo "UPDATE $table SET name=$name WHERE id=$id<br />\n";
				if(!$st_update->execute(array($name,$id)))
				{
					$errorinfo=$this->db->errorInfo();
					trigger_error("SQL error updating category name: $errorinfo[2]",E_USER_WARNING);
				}
				$categories_db[$id]=$name; //Update the name variable to avoid reloading from db
			}

			if($_POST['visible'][$id]!=$visible[$id])
			{
				echo "UPDATE $table SET visible={$_POST['visible'][$id]} WHERE id=$id<br />\n"; //Dummy query for troubleshooting
				if(!$st_visible_update->execute(array($_POST['visible'][$id],$id)))
				{
					$errorinfo=$this->db->errorInfo();
					trigger_error("SQL error updating visibility: $errorinfo[2]",E_USER_WARNING);
				}
				$visible_db[$id]=$visible[$id];
			}

		}
		$visible=$_POST['visible'];
	}
	//Create table
	$table=$dom->createElement('table');
	$table->setAttribute('border','1');
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
		$input_visible=$input_delete->cloneNode(true);
		$input_visible->setAttribute('name',"visible[$id]");
		if($visible_db[$id])
			$input_visible->setAttribute('checked','checked');
		$td_visible->appendChild($input_visible);
		$tr->appendChild($td_visible); //Add the column to the the row

		$table->appendChild($tr); //Add the row to the table
	}
	echo $dom->saveXML($dom->documentElement);
	echo '<input name="submit" type="submit">';
}
?>
</form>
</body>
</html>