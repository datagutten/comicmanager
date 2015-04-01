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
require '../class.php';
$comicmanager=new comicmanager;
$comicinfo=$comicmanager->comicinfo_get();

require '../tools/DOMDocument_createElement_simple.php';
$dom=new DOMDocumentCustom;
$dom->formatOutput = true;

if($comicinfo!==false)
{
	$comic=$comicinfo['id'];
	if(!isset($_GET['key']))
	{
		$form=$dom->createElement_simple('form',$dom,array('method'=>'get','id'=>'form'));
		$dom->createElement_simple('input',$form,array('name'=>'comic','type'=>'hidden','value'=>$comicinfo['id'])); //Comic ID
		$dom->createElement_simple('label',$form,array('for'=>'key','id'=>'label_key'),ucfirst($comicinfo['keyfield']).':');
		$input=$dom->createElement_simple('input',$form,array('type'=>'text','id'=>'key','name'=>'key'));

		if($comicinfo['keyfield']=='customid')
		{
			$span_id_checkbox=$dom->createElement_simple('span',$form,array('id'=>'span_id'));
			$dom->createElement_simple('input',$span_id_checkbox,array('type'=>'checkbox','id'=>'id_checkbox','onclick'=>"change_key_field('id')"));
			$dom->createElement_simple('label',$span_id_checkbox,array('for'=>'id_checkbox','id'=>'label_id_checkbox'),'id');
		}
		
		$dom->createElement_simple('input',$form,array('name'=>'submit','type'=>'submit'));
		echo $dom->saveXML($form);
	}
	else
	{
		if(!isset($_GET['keyfield']))
			$keyfield=$comicinfo['keyfield'];
		else
			$keyfield=$_GET['keyfield'];
		$key=preg_replace('/[^a-z0-9]+/i','',$_GET['key']); //Clean key
		
		$st_strip=$comicmanager->db->prepare("SELECT * FROM $comic WHERE $keyfield=? ORDER BY date DESC");
		$st_update_category=$comicmanager->db->prepare("UPDATE $comic SET category=? WHERE $keyfield=?");
	
		if(isset($_POST['submit']))
		{
			$st_strip->execute(array($key));
			$strips=$st_strip->fetchAll(PDO::FETCH_ASSOC);
	
			foreach($_POST['strip'] as $key_strip=>$strip)
			{
				foreach($strip as $key_field=>$field)
				{
					if($strips[$key_strip][$key_field]==$field)
						continue;
					echo "UPDATE $comic SET $key_field=$field WHERE uid={$strip['uid']};\n";		
				}
			}
			if(!empty($_POST['category']) && is_numeric($_POST['category']))
			{
				echo "UPDATE $comic SET category={$_POST['category']} WHERE $keyfield=$key;<br />\n";
				$st_update_category->execute(array($_POST['category'],$key));
			}
		}
		
		
		//Re-read from database after form submit
		$st_strip->execute(array($_GET['key']));
		$strips=$st_strip->fetchAll(PDO::FETCH_ASSOC);

		$comicmanager->showpicture($strips[0],$keyfield);
		
		$form=$dom->createElement_simple('form',$dom,array('method'=>'post')); //Create form
		if($comicinfo['has_categories']==1)
		{
			$categories=$comicmanager->categories($comic);
			$strips_categories=array_unique(array_column($strips,'category')); //Get categories for the strips
			if(count($strips_categories)>1)
			{
				echo "<p>Strip got multiple categories:<br />\n";
				foreach($strips_categories as $category_id)
				{
					echo $categories[$category_id]."<br />\n";
				}
				echo "</p>\n";
			}
			
			//Category select
			$select=$dom->createElement_simple('select',$form,array('name'=>'category'));
			$option_default=$dom->createElement_simple('option',$select,array('selected'=>'selected'),"Change category");
			foreach ($categories as $category_id=>$category_name)
			{
				$option=$dom->createElement_simple('option',$select,array('value'=>$category_id),$category_name);
			}
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
}
?>
</body>
</html>