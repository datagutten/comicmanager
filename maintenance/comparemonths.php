<?php
$file=__FILE__;
require 'loader.php';
if(isset($_POST['submit']))
{
?>
<table width="200" border="1">
<?Php
$comic=$comicinfo['id'];

$start=$_POST['start'];
$site=$_POST['site'];

$keyfield='customid';

$st_strip=$comicmanager->db->prepare("SELECT * FROM $comic WHERE date=? AND site=?");

for($i=0; $i<=40; $i++) //Loop through the days
{
	echo "<tr>\n";
	foreach($_POST['start'] as $key=>$startdate) //Loop through the strips
	{
		if(empty($startdate))
			continue;
		$startpos=strtotime($startdate);
		if($i==0)
			$time[$key]=$startpos;
		else
			$time[$key]=strtotime("+1 day",$time[$key]);
		
		if(date('D',$time[$key])=='Sun')
			$time[$key]=strtotime("+1 day",$time[$key]); //Skip sundays
		{

		$st_strip->execute(array(date('Ymd',$time[$key]),$site[$key]));

		echo "<td>\n";
		if($st_strip->rowCount()>0)
		{
			$row=$st_strip->fetch();
			echo $row['id'].' - ';	
			echo $comicmanager->dom->saveXML($comicmanager->showpicture($row,$keyfield,$comic));
		}
		else
			echo "No strips found for {$site[$key]} ".date('Y-m-d',$time[$key]);
		echo "</td>\n";
		}
	}

echo "</tr>\n";
}
?>
</table>
<?Php
}
else
{
	?>
	<form id="form1" name="form1" method="post">
<div id="fields"></div>
  <p><input type="submit" name="submit" id="submit" value="Submit"></p>
</form>
<script type="text/javascript">
const newcount=1;

function addfield() //Create a new table row with an empty field
{
	const div=document.getElementById('fields');

    const label_date=document.createElement('label');
	label_date.innerHTML="Start date:";
	div.appendChild(label_date);

    const input_date=document.createElement('input');
	input_date.setAttribute('type','text'); //type="text"
	input_date.setAttribute('name',"start[]");
	input_date.setAttribute('onchange','addfield()');
	div.appendChild(input_date);

    const label_site=document.createElement('label');
	label_site.innerHTML="&nbsp;Site:";
	div.appendChild(label_site);

    const input_site=document.createElement('input');
	input_site.setAttribute('type','text'); //type="text"
	input_site.setAttribute('name',"site[]");

	div.appendChild(input_site);

	div.appendChild(document.createElement('br'));
}
addfield();
</script>
<?php
}
?>