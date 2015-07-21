<?Php
require 'class.php';
$comicmanager=new comicmanager;
$title='Show comics';
if(isset($_GET['comic']))
{
	$comicinfo=$comicmanager->comicinfo($_GET['comic'],(isset($_GET['keyfield']) ? $_GET['keyfield'] : false));
	$title="Show ".$comicinfo['name'];
}
if(isset($_GET['view']))
{
	switch($_GET['view'])
	{
		case 'singlestrip': $title.=' strip '.$_GET['value']; break;
		case 'category': $title.=' category '.$_GET['value']; break;
	}
}

if(!isset($_GET['noheader']))
{
?>
<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo $title; ?></title>
</head>

<body>
<?Php
}

/*Rekkefølge på parametere:
comic
view
[keyfield]
[site]
value
*/
if(!isset($_GET['comic']))
	echo $comicmanager->selectcomic();
elseif(isset($comicinfo))
{
	$comic=$comicinfo['id'];
	$keyfield=$comicinfo['keyfield'];

	$hidedupes=false;
	if(count($_GET)>1) //Parameters specified
	{
		if($_GET['view']=='singlestrip') //Show strip by key
		{
			if(is_numeric($_GET['value']))
				$where="$keyfield=".$_GET['value'];
			elseif(strpos($_GET['value'],',')) //Multiple keys is separated by comma
			{
				$where="$keyfield=";
				$where.=str_replace(','," OR $keyfield=",$_GET['value']);
			}
			else
				die('Invalid key');
			$st_show=$comicmanager->db->prepare($q="SELECT * FROM $comic WHERE $where");
			if(!$st_show->execute())
				die(print_r($st_show->errorInfo()));
		}
		elseif($_GET['view']=='date') //Find by date
		{
			if(!isset($_GET['value']) || !isset($_GET['site']))
				die('Date and site must be specified');
			if(!preg_match('/^[0-9%]+$/',$_GET['value'],$matches))
				die("Invalid date: ".$_GET['value']);
			$st_show=$comicmanager->db->prepare($q="SELECT * FROM {$comicinfo['id']} WHERE date LIKE ? AND site=? ORDER BY date");
	
			if(!$st_show->execute(array($_GET['value'],$_GET['site'])))
			{
				$errorinfo=$st_show->errorInfo();
				trigger_error("SQL error: $errorinfo[2]",E_USER_ERROR);
			}
			if($st_show->rowCount()==0)
				die('No strips found for the specified date');
	
		}
		elseif($_GET['view']=='range' && is_numeric($min=$_GET['from']) && is_numeric($max=$_GET['to'])) //Show a range (from key to key)
		{
			$where="$keyfield>=$min AND $keyfield<=$max GROUP BY $keyfield ORDER BY $keyfield";
			$st_show=$comicmanager->db->prepare("SELECT * FROM {$comicinfo['id']} WHERE $keyfield>=? AND $keyfield<=? GROUP BY $keyfield ORDER BY $keyfield");
			$st_show->execute(array($_GET['from'],$_GET['to']));
		}
		elseif($_GET['view']=='category')
		{
			$st_show=$comicmanager->db->prepare("SELECT * FROM {$comicinfo['id']} WHERE category=? ORDER BY date");
			$st_show->execute(array($_GET['value']));
			$hidedupes=true;
		}
		else
			trigger_error('Invalid view: '.$_GET['view'],E_USER_ERROR);
	
		$displayed_strips=array();
		$count=0; //Initialize counter variable
		foreach($st_show->fetchAll(PDO::FETCH_ASSOC) as $row)
		{
			if(is_numeric($row[$keyfield]) && $hidedupes)
			{
				if(array_search($row[$keyfield],$displayed_strips)!==false) //Check if another version of the strip already has been displayed
					continue;
				$displayed_strips[]=$row[$keyfield]; //Add current key to the list of displayed strips
				if($_GET['view']!='date')
				{
					$st_nyeste=$comicmanager->db->prepare("SELECT * FROM $comic WHERE $keyfield=? ORDER BY date DESC LIMIT 1"); //Finn nyeste utgave av stripen
					$st_nyeste->execute(array($row[$keyfield]));
					$row=$st_nyeste->fetch();
				}
			}
			
			$comicmanager->showpicture($row,$keyfield);
			$count++;
		}

		echo "<p>Number of displayed strips: $count</p>\n";
	}
	else //Show default view
	{
		echo "<h1>{$comicinfo['name']}</h1>\n";
		?>
		<h2>Show strips by date</h2>
		<form id="form1" name="form1" method="get" action="showcomics.php">
			<input name="comic" type="hidden" id="comic1" value="<?php echo $comic; ?>" />
			<input name="view" type="hidden" value="date" />
			Site: <input name="site" type="text" id="site" value="" />
			Date: <input type="text" name="value" id="value" /> (Use % as wildcard)
			<input type="submit" value="Submit" />
		</form>
	
		<h2>Show strip by key</h2>
		<form id="form2" name="form2" method="get" action="showcomics.php?view=singlestrip">
	  <input name="comic" type="hidden" id="comic2" value="<?php echo $comic; ?>" />
			<input name="view" type="hidden" value="singlestrip" />
			ID:
		<?Php if($comicinfo['keyfield']=='customid') { ?>
		   <input name="keyfield" type="checkbox" id="keyfield" value="id" />
	<?Php } ?>
		  <input name="value" type="text" value="" />
		  <input type="submit" value="Submit">
		</form>
	<h2>Show strip range</h2>
		<form id="form3" name="form3" method="get" action="showcomics.php">
			<input name="comic" type="hidden" id="comic3" value="<?php echo $comic; ?>" />
			<input name="view" type="hidden" value="range" />
		From ID: <input name="from" type="text" id="key_from" size="5">
		  To ID: <input name="to" type="text" id="id_max" size="5">
		  <input type="submit" value="Submit">
		 </form>
	<?Php
		$st_range=$comicmanager->db->query("SELECT MIN({$comicinfo['keyfield']}) AS min, MAX({$comicinfo['keyfield']}) AS max FROM {$comicinfo['id']}");
		$range=$st_range->fetch(PDO::FETCH_ASSOC);
		echo "<p>First id: {$range['min']} Last id: {$range['max']}</p>\n";
	
		if($comicinfo['has_categories']==1) //Check if the comic got categories
		{
			echo "<h2>Categories</h2>\n";
			$st_categories=$comicmanager->db->prepare($q="SELECT * FROM {$comic}_categories ORDER BY name ASC");
			if(!$st_categories->execute())
			{
				$errorinfo=$st_categories->errorInfo();
				trigger_error("SQL error while fetching categories: $errorinfo[2]",E_USER_WARNING);
			}
			else
			{
				foreach($st_categories->fetchAll() as $row)
				{
					echo "<a href=\"?comic={$_GET['comic']}&amp;view=category&amp;value={$row['id']}\">{$row['name']}</a><br>\n";
				}
			}
		}		
	}
}
if(!isset($_GET['noheader']))
{
	if(count($_GET)==1)
		echo "<p><a href=\"?\">Back to comic selection</a></p>\n";
	elseif(count($_GET)>1)
		echo "<p><a href=\"?comic=$comic\">Back to {$comicinfo['name']}</a></p>\n";
?>
</body>
</html><?Php
}
?>