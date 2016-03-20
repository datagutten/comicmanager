
<?Php
require 'class.php';
$comicmanager=new comicmanager;
require 'tools/DOMDocument_createElement_simple.php';
$dom=new DOMDocumentCustom;
//$title='Show comics';
if(isset($_GET['comic']))
	$comicinfo=$comicmanager->comicinfo($_GET['comic']);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Comicmanager</title>
</head>


<?Php
if(!isset($_GET['comic']))
	echo $comicmanager->selectcomic();
elseif($comicinfo!==false)
{
	$body=$dom->createElement('body');
	$h1=$dom->createElement_simple('h1',$body,false,$comicinfo['name']);
	$ul=$dom->createElement_simple('ul',$body);
	
	$li=$dom->createElement_simple('li',$ul);
	$a=$dom->createElement_simple('a',$li,array('href'=>'showcomics.php?comic='.$comicinfo['id']),'Show comics');

	$li=$dom->createElement_simple('li',$ul);
	$a=$dom->createElement_simple('a',$li,array('href'=>'management/?comic='.$comicinfo['id']),'Manage');

	$li=$dom->createElement_simple('li',$ul);
	$a=$dom->createElement_simple('a',$li,array('href'=>'maintenance/?comic='.$comicinfo['id']),'Maintain');
	
	echo $dom->saveXML($body);
}
?>

</html>