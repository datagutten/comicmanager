<?Php

use datagutten\comicmanager\elements;
use datagutten\comicmanager\exceptions;
use datagutten\comicmanager\web;

require '../vendor/autoload.php';
$comicmanager=new web;

if(isset($_POST['submit']))
{
    if(empty($_POST['key_field']))
        die($comicmanager->render('error.twig', array('error'=>'Missing key field')));
    if(!isset($_POST['extra_keys']))
        $_POST['extra_keys'] = [];

	try {
	    $fields = ['id'=>$_POST['comic'], 'name'=>$_POST['name'], 'key_field'=>$_POST['key_field'], 'has_categories'=>isset($_POST['has_categories'])];
	    $fields['possible_key_fields'] = array_merge([$_POST['key_field']], $_POST['extra_keys']);
	    $comic = new elements\Comic($comicmanager->config['db'], $fields);
        $comic->create();
        //$setup->createComic(strtolower($_POST['comic']), $_POST['name'], $_POST['key_field'], isset($_POST['has_categories']), $_POST['extra_keys']);
        header('Location: managecomics_front.php?comic='.$_POST['comic']);
    }
	catch (exceptions\comicManagerException $e)
	{
		echo $comicmanager->render('error.twig', array('error'=>'Error adding comic: '.$e->getMessage(), 'trace'=>$e->getTraceAsString()));
	}
}
else
    echo $comicmanager->render('add_comic.twig', array('title'=>'Add comic', 'key_fields'=> elements\Comic::$key_fields));