<?Php

use datagutten\comicmanager\web;

if(isset($argv[1]))
    $_GET['comic'] = $argv[1];
require '../vendor/autoload.php';

$comicmanager=new web();
$comicinfo=$comicmanager->comicinfo_get();

if(is_array($comicinfo) && $comicinfo['has_categories']==1)
{
	$table=$comicinfo['id']."_categories";
	$st_update_visibility=$comicmanager->db->prepare("UPDATE $table SET visible=? WHERE id=?");
	$st_insert=$comicmanager->db->prepare("INSERT INTO $table (name,visible) VALUES (?,?)");
	$st_delete=$comicmanager->db->prepare("DELETE FROM $table WHERE id=?");
    $st_update_name=$comicmanager->db->prepare("UPDATE $table SET name=? WHERE id=?");

	if(isset($_POST['submit']))
	{
	    $messages = [];
        foreach ($comicmanager->categories(false, true) as $category)
        {
            try {
                $id=$category['id'];
                if(isset($_POST['categories'][$id]['delete']))
                {
                    $st_delete->execute(array($id));
                    continue;
                }
                if($_POST['categories'][$id]['name']!=$category['name'])
                {
                    $messages[]="Name changed to ".$_POST['categories'][$id]['name'];
                    $st_update_name->execute(array($_POST['categories'][$id]['name'], $id));
                }

                if(!isset($_POST['categories'][$id]['visible']))
                    $_POST['categories'][$id]['visible'] = '0';

                if($_POST['categories'][$id]['visible'] != $category['visible'])
                    $st_update_visibility->execute(array($_POST['categories'][$id]['visible'], $id));
            }
            catch (PDOException $e)
            {
                echo $e->getMessage();
            }
        }
        foreach ($_POST['new'] as $new)
        {
            if(empty($new['name']))
                continue;
            if(!isset($new['visible']))
                $new['visible'] = '0';
            $st_insert->execute(array($new['name'], $new['visible']));
        }
	}
    echo $comicmanager->render('edit_categories.twig', array( 'name' => 'Comics ID',
        'title' => sprintf('%s categories', $comicinfo['name']),
        'categories' => $comicmanager->categories(false, true),
        'messages' => $messages,
        'js' => 'add_category.js',
        'extra_css' => 'table_border.css',
    ));

}
elseif($comicinfo['has_categories']==0)
    $comicmanager->render('error.twig', array(
        'title'=>'Error',
        'error'=>sprintf('%s does not have categories', $comicinfo['name']),));
