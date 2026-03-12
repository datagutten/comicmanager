<?Php

use datagutten\comicmanager\exceptions;
use datagutten\comicmanager\maintenance\Maintenance;
use datagutten\comicmanager\web;

require '../vendor/autoload.php';

$comicmanager=new web;

$tools=array(
    'id_to_customid'=>'Set id as customid',
    'propagate_categories'=>"Propagate category to all copies of a strip",
    'multiple_categories' => 'Find strips with multiple categories',
    'propagate_id'=>"Propagate id to all copies of a strip",
    );

$comicinfo=$comicmanager->comicinfo_get();
$maintenance = new Maintenance($comicmanager, true);
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
    else
    {
        try
        {
            if ($_GET['tool'] == 'propagate_categories')
                $output = $maintenance->propagateCategories();
            elseif ($_GET['tool'] == 'id_to_customid')
                $output = $maintenance->idToCustomId();
            elseif ($_GET['tool'] == 'propagate_id')
                $output = $maintenance->propagateId();
            elseif ($_GET['tool'] == 'multiple_categories')
                $output = $maintenance->multipleCategories();
            else
                echo "Invalid tool: {$_GET['tool']}";
        }
        catch (exceptions\InvalidMaintenanceTool $e)
        {
            die($comicmanager->render_error($e->getMessage()));
        }
    }

    if (isset($output))
    {
        echo $comicmanager->render('tool_output.twig', array(
            'title' => sprintf('%s: %s', $comicinfo->name, $tools[$_GET['tool']]),
            'output' => nl2br(implode('<br />', $output))));
    }
}