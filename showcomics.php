<?Php
if(!isset($_GET['comic']))
{
	header('Location: showcomics_front.php');
	die();
}
elseif(!isset($_GET['view']))
{
	header('Location: showcomics_front.php?comic='.$_GET['comic']);
	die();
}

require 'class.php';
$comicmanager=new comicmanager;
$dom=$comicmanager->dom;
//Create HTML header
$html=$dom->createElement_simple('html');
$head=$dom->createElement_simple('head',$html);
$dom->createElement_simple('meta',$head,array('charset'=>'utf-8'));
$dom->createElement_simple('link',$head,array('href'=>'comicmanager.css','rel'=>'stylesheet','type'=>'text/css'));
$title=$dom->createElement_simple('title',$head,false,'Show comics');
$body=$dom->createElement_simple('body',$html);

$comicinfo=$comicmanager->comicinfo_get();
$title->textContent='Show '.$comicinfo['name'];

/*Rekkefølge på parametere:
comic
view
[keyfield]
[site]
value
*/

if(!empty($comicinfo))
{
	$comic=$comicinfo['id'];
	$keyfield=$comicinfo['keyfield'];

	$hidedupes=false;

		if($_GET['view']=='singlestrip') //Show strip by key
		{
			if(!preg_match('/^[a-zA-Z0-9%,]+$/',$_GET['value'],$value))
				$dom->createElement_simple('div',$body,array('class'=>'error'),'Invalid characters in key: '.$_GET['value']);
			elseif(strpos($_GET['value'],',')!==false) //Multiple keys separated by comma
			{
				$values=explode(',',$_GET['value']);
				$where=sprintf("%s='%s'",$comicinfo['keyfield'],array_shift($values));
				foreach($values as $value)
				{
					$where.=sprintf("OR %s='%s'",$comicinfo['keyfield'],$value);
				}
				$hidedupes=true;
			}
			elseif(strpos($_GET['value'],'%')!==false) //Wildcard
			{
				$where=sprintf('%1$s LIKE \'%2$s\' ORDER BY %1$s',$keyfield,$value[0]);
				$hidedupes=true;
			}
			else
				$where=sprintf("%s='%s'",$comicinfo['keyfield'],$value[0]);

			if(isset($where))
				$title->textContent.=' strip '.$_GET['value'];		

		}
		elseif($_GET['view']=='date') //Find by date
		{
			$sites=$comicmanager->sites();
			if(!isset($_GET['value']) || !isset($_GET['site']))
				$dom->createElement_simple('div',$body,array('class'=>'error'),'Date and site must be specified');
			elseif(!preg_match('/^[0-9%]+$/',$_GET['value'],$date))
				$dom->createElement_simple('div',$body,array('class'=>'error'),'Invalid date value: '.$_GET['value']);
			elseif(array_search($_GET['site'],$sites)===false)
				$dom->createElement_simple('div',$body,array('class'=>'error'),'Invalid site: '.$_GET['site']);
			else
			{
				$date=$date[0];
				$title->textContent.=' '.$date;
				if(strlen($date)==6)
					$date=$date.'%';

				//$st_show=$comicmanager->db->prepare($q="SELECT * FROM {$comicinfo['id']} WHERE date LIKE ? AND site=? ORDER BY date");
				$where=sprintf("date LIKE '%s' AND site='%s' ORDER BY date",$date,$_GET['site']);
				//$comicmanager->execute($st_show,array($date,$_GET['site']));
				$empty_message=sprintf('No strips found for date %s on site %s',$date,$_GET['site']);
			}
		}
		elseif($_GET['view']=='range') //Show a range (from key to key)
		{
			if(!is_numeric($min=$_GET['from']) || !is_numeric($max=$_GET['to']))
				$dom->createElement_simple('div',$body,array('class'=>'error'),'From and to values must be numeric');
			else
			{
				$where=sprintf('%1$s>=%2$d AND %1$s<=%3$d ORDER BY %1$s',$comicinfo['keyfield'],$min,$max);
				$title->textContent.=sprintf(' %s %s to %s',$comicinfo['keyfield'],$min,$max);
				$hidedupes=true;
			}
		}
		elseif($_GET['view']=='category')
		{
			if(!is_numeric($_GET['value']))
			   $dom->createElement_simple('div',$body,array('class'=>'error'),'Category id must be numeric');
			else
			{
				$where=sprintf('category=%d ORDER BY date',$_GET['value']);
				$title->textContent.=' category '.$_GET['value'];
				$hidedupes=true;
			}
			$emptymessage='Invalid category: '.$_GET['value'];
		}
		else
			$dom->createElement_simple('div',$body,array('class'=>'error'),'Invalid view: '.$_GET['view']);
	if(!isset($st_show) && isset($where))
		$st_show=$comicmanager->db->query($q=sprintf('SELECT * FROM %s WHERE %s',$comicinfo['id'],$where));

	if(isset($st_show))
	{
		$displayed_strips=array();
		$count=0; //Initialize counter variable
		$dom->createElement_simple('h1',$body,false,$title->textContent);
		while($row=$st_show->fetch(PDO::FETCH_ASSOC))
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

			$div_release=$comicmanager->showpicture($row,$keyfield);
			$body->appendChild($div_release);
			$count++;
		}
		if($st_show->rowCount()>0)
			$dom->createElement_simple('p',$body,false,'Number of displayed strips: '.$count);
		elseif(isset($empty_message))
			$dom->createElement_simple('p',$body,array('class'=>'error'),$empty_message);
		else
			$dom->createElement_simple('p',$body,array('class'=>'error'),'No strips found');
	}
}

$div_footer=$dom->createElement_simple('div',$body);
$p=$dom->createElement_simple('p',$div_footer);
$dom->createElement_simple('a',$p,array('href'=>'?'),'Change comic');
$p=$dom->createElement_simple('p',$div_footer);
$dom->createElement_simple('a',$p,array('href'=>'?comic='.$comicinfo['id']),'Show more '.$comicinfo['name']);
$p=$dom->createElement_simple('p',$div_footer);
$dom->createElement_simple('a',$p,array('href'=>'index.php?comic='.$comicinfo['id']),'Main menu');

echo "<!DOCTYPE HTML>\n";
echo $dom->saveXML($html);
?>