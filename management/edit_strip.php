<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Edit strip</title>
<script type="text/javascript">
function change_key_field(new_key_field)
{
	label=document.getElementById('label_key');
	label.textContent=new_key_field;
	
	form=document.getElementById('form');
	input=document.createElement('input');
	input.setAttribute('type','hidden');
	input.setAttribute('name','keyfield');
	input.setAttribute('value',new_key_field);
	form.appendChild(input);
	
	span=document.getElementById('span_'+new_key_field);
	form.removeChild(span);
}
</script>
</head>

<body>
<?php
require 'class_management.php';
$comicmanager=new management;
$comicinfo=$comicmanager->comicinfo_get();

$dom=$comicmanager->dom;

$dom->formatOutput = true;

if($comicinfo!==false) //A valid comid is selected, show form to select strip
{
	$comic=$comicinfo['id'];
	if(!isset($_GET['key']))
	{
		$form=$dom->createElement_simple('form',$dom,array('method'=>'get','id'=>'form')); //Create GET form
		$dom->createElement_simple('input',$form,array('name'=>'comic','type'=>'hidden','value'=>$comicinfo['id'])); //Comic ID

		$select=$dom->createElement_simple('select',$form,array('name'=>'keyfield')); //Create keyfield select
		foreach($comicinfo['possible_key_fields'] as $option_value) //Add options to the select list
		{
			$option=$dom->createElement_simple('option',$select,false,$option_value);
			if($option_value===$comicinfo['keyfield'])
				$option->setAttribute('selected','selected');
		}

		$input=$dom->createElement_simple('input',$form,array('type'=>'text','id'=>'key','name'=>'key'));

		$dom->createElement_simple('input',$form,array('type'=>'submit'));
		echo $dom->saveXML($form);
	}
	else //Strip selected
	{
		if(!isset($_GET['keyfield']))
			$keyfield=$comicinfo['keyfield'];
		else
			$keyfield=$_GET['keyfield'];
		$key=preg_replace('/[^a-z0-9]+/i','',$_GET['key']); //Clean key

		//Prepare SQL statements
		$st_strip=$comicmanager->db->prepare("SELECT * FROM $comic WHERE $keyfield=? ORDER BY date DESC");
		$st_update_category=$comicmanager->db->prepare("UPDATE $comic SET category=? WHERE $keyfield=?");
	
		if(isset($_POST['submit'])) //Edit form submitted
		{
			$st_strip->execute(array($key));
			$strips=$st_strip->fetchAll(PDO::FETCH_ASSOC);

			foreach($_POST['strip'] as $strip_key=>$strip_fields)
			{
				foreach($strip_fields as $field_key=>$field_value) //Loop through the fields
				{
					if(!array_key_exists($field_key,$strips[$strip_key])) //Check if form field is a db field
					{
						echo "Invalid field: $field_key ($strip_key)<br />\n";
						continue;
					}
					if($strips[$strip_key][$field_key]==$field_value) //Check if information in database is different from the form
						continue;
					if(empty($field_value))
						$field_value=NULL;
					echo "UPDATE $comic SET $field_key=$field_value WHERE uid={$strip_fields['uid']};<br />\n";
					$st_update_field=$comicmanager->db->prepare("UPDATE $comic SET $field_key=? WHERE uid=?");
					if(!$st_update_field->execute(array($field_value,$strip_fields['uid'])))
					{
						$errorinfo=$st_update_field->errorInfo();
						trigger_error("SQL error: {$errorinfo[2]}",E_USER_WARNING);
					}
				}
			}
			if((is_numeric($_POST['category']) && !isset($_POST['current_category'])) || (isset($_POST['current_category']) && $_POST['category']!=$_POST['current_category'])) //Update category if non-empty, numeric and changed
			{
				echo "UPDATE $comic SET category={$_POST['category']} WHERE $keyfield=$key;<br />\n";
				$st_update_category->execute(array($_POST['category'],$key));
			}
		}
		
		
		//Re-read from database after form submit
		$st_strip->execute(array($_GET['key']));
		if($st_strip->rowCount()>0)
		{
			$strips=$st_strip->fetchAll(PDO::FETCH_ASSOC);

			$form=$dom->createElement_simple('form',$dom,array('method'=>'post')); //Create form
            $context=array(
                'release'=> $strips[0],
                'root'=>$comicmanager->root,
                'comic'=>$comicinfo,);

            $image = $comicmanager->twig->render('image.twig', $context);
			$image = str_replace('&nbsp;', ' ', $image);
            $picture = $dom->createDocumentFragment();
            $picture->appendXML($image);
            $form->appendChild($picture);

			if($comicinfo['has_categories']==1)
			{
				$categories=$comicmanager->categories();
				$strips_categories=array_filter(array_unique(array_column($strips,'category'))); //Get categories for the strips
				if(empty($strips_categories))
					$category_preselect=false;
				elseif(count($strips_categories)>1)
				{
					echo "<p>Strip got multiple categories:<br />\n";
					foreach($strips_categories as $category_id)
					{
						echo $categories[$category_id]."<br />\n";
					}
					echo "</p>\n";
					$category_preselect=false;
				}
				else
				{
					$category_preselect=array_values($strips_categories);
					$category_preselect=$category_preselect[0];
					$comicmanager->dom->createElement_simple('input',$form,array('type'=>'hidden','name'=>'current_category','value'=>$category_preselect)); //Hidden field with category before change
				}

				$comicmanager->categoryselect('category',$form,$category_preselect); //Category select
			}
			
			$table=$dom->createElement_simple('table',$form); //Create the table
			$tr_keys=$dom->createElement_simple('tr',$table); //Create the header row
		
			foreach($strips as $key_strip=>$strip)
			{
				$tr=$dom->createElement_simple('tr',$table); //Make a row
				foreach($strip as $key_field=>$field)
				{
					if($key_strip==0)
						$dom->createElement_simple('td',$tr_keys,'',$key_field); //Add the field name to the header row
					
					$td=$dom->createElement_simple('td',$tr);
					$dom->createElement_simple('input',$td,array('type'=>'text','name'=>'strip['.$key_strip.']['.$key_field.']','value'=>$field));
				}
			}
			$dom->createElement_simple('input',$form,array('name'=>'submit','type'=>'submit'));
			echo $dom->saveXML($form);
		}
		else
			echo "Strip not found";
		echo "<p><a href=\"?comic={$_GET['comic']}\">Edit another strip</a></p>\n";
	}
	echo "<p><a href=\"index.php?comic={$comicinfo['id']}\">Manage {$comicinfo['name']}</a></p>\n";
	echo "<p><a href=\"../showcomics.php?comic={$comicinfo['id']}\">Show {$comicinfo['name']}</a></p>\n";
}
?>
</body>
</html>