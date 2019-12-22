<?Php
require '../vendor/autoload.php';
$comicmanager=new comicmanager;
$tools=array(
    'id_to_customid.php'=>'Set id as customid',
    'propagate_categories.php'=>"Propagate category to all copies of a strip",
    'multiple_categories.php'=>'Find strips with multiple categories',
    'propagate_id.php'=>"Propagate id to all copies of a strip",
    'traceid.php'=>'Trace ID',
    );

$comicinfo=$comicmanager->comicinfo_get();
if($comicinfo!==false)
{
	$comic=$comicinfo['id'];
	if($comicinfo['has_categories']==0)
		unset($tools['propagate_categories.php'],$tools['multiple_categories.php']);
	if(count(array_intersect(array('customid','id'),$comicinfo['possible_key_fields']))!=2)
		unset($tools['id_to_customid.php']);
	if(array_search('id',$comicinfo['possible_key_fields'])===false || count($comicinfo['possible_key_fields'])==1)
		unset($tools['propagate_id.php'],$tools['traceid.php']);
	
	if(!isset($_GET['tool']))
	{
	    echo $comicmanager->render('select_tool.twig', array(
	            'title'=>'Maintain '.$comicinfo['name'],
                'tools'=>$tools,
                'header'=>'Tools for maintaining the database'));
	}
	elseif(isset($tools[$_GET['tool']]))
	{
	    ob_start();
        /** @noinspection PhpIncludeInspection */
	    require $_GET['tool'];
	    $output = ob_get_clean();
	    echo $comicmanager->render('tool_output.twig', array(
	            'tool'=>$tools[$_GET['tool']],
                'output'=>$output));
		//echo "<h3>{$_GET['tool']}</h3>\n";
		//echo "<p><a href=\"?comic=$comic\">Back to tool selection</a></p>\n";
	}
	else
		echo "Invalid tool: {$_GET['tool']}";
}