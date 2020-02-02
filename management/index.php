<?Php

use datagutten\comicmanager\web;

require '../vendor/autoload.php';
$comicmanager=new web();
$tools=array(
    'edit_categories.php'=>"Add or edit categories",
    'managecomics_front.php'=>'Add id or category to strips',
    'edit_release.php'=>'Edit release',
    'add_id_files.php'=>'Add files with id as name to the database',
    'compare_months.php'=>'Compare months and add missing releases'
);

$comicinfo=$comicmanager->comicinfo_get();
if($comicinfo!==false)
{
	$comic=$comicinfo['id'];
	if($comicinfo['has_categories']==0)
	{
		unset($tools['edit_categories.php']);
		$tools['enable_categories.php'] = 'Enable categories';
	}
	if(array_search('id',$comicinfo['possible_key_fields'])===false)
		unset($tools['add_id_files.php']);

	if(!isset($_GET['tool']))
	{
	    echo $comicmanager->render('select_tool.twig', array(
            'comic'=>$comicinfo,
            'tools'=>$tools,
            'title'=>'Manage '.$comicinfo['name'],
            'root'=>$comicmanager->root,
        ));
	}
	elseif(isset($tools[$_GET['tool']]))
	{
		header('Location: '.$_GET['tool'].'?comic='.$_GET['comic']);
	}
	else
		echo "Invalid tool: {$_GET['tool']}";
	
}
