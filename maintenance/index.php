<?Php
use datagutten\comicmanager\maintenance\Maintenance;
use datagutten\comicmanager\web;

require '../vendor/autoload.php';
$config = require __DIR__.'/../comicmanager/config.php';

$comicmanager=new web;

$tools=array(
    'id_to_customid'=>'Set id as customid',
    'propagate_categories'=>"Propagate category to all copies of a strip",
    'multiple_categories.php'=>'Find strips with multiple categories',
    'propagate_id.php'=>"Propagate id to all copies of a strip",
    );

$comicinfo=$comicmanager->comicinfo_get();
$maintenance = new Maintenance($comicmanager);
if($comicinfo!==false)
{
	$comic=$comicinfo['id'];
	if($comicinfo['has_categories']==0)
		unset($tools['propagate_categories.php'],$tools['multiple_categories.php']);
	if(count(array_intersect(array('customid','id'),$comicinfo['possible_key_fields']))!=2)
		unset($tools['id_to_customid.php']);
	if(!isset($_GET['tool']))
	{
	    echo $comicmanager->render('select_tool.twig', array(
	            'title'=>'Maintain '.$comicinfo['name'],
                'tools'=>$tools,
                'header'=>'Tools for maintaining the database'));
	}
	elseif($_GET['tool']=='propagate_categories')
        $output = $maintenance->propagateCategories();
	elseif($_GET['tool']=='id_to_customid')
        $output = $maintenance->idToCustomId();
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

    if (isset($output))
    {
        echo $comicmanager->render('tool_output.twig', array(
            'tool' => $tools[$_GET['tool']],
            'output' => implode('<br />', $output)));
    }
}