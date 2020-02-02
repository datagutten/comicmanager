<?Php

use datagutten\comicmanager\metadata;
use datagutten\comicmanager\setup;
use datagutten\comicmanager\web;

require '../vendor/autoload.php';
$comicmanager=new web;
$setup = new setup();

if(isset($_POST['submit']))
{
    if(empty($_POST['key_field']))
        die($comicmanager->render('error.twig', array('error'=>'Missing key field')));
    if(!isset($_POST['extra_keys']))
        $_POST['extra_keys'] = [];

	try {
        $setup->createComic(strtolower($_POST['comic']), $_POST['name'], $_POST['key_field'], isset($_POST['has_categories']), $_POST['extra_keys']);
        header('Location: managecomics_front.php?comic='.$_POST['comic']);
    }
	catch (PDOException|InvalidArgumentException $e)
	{
		echo $comicmanager->render('error.twig', array('error'=>'Error adding comic: '.$e->getMessage(), 'trace'=>$e->getTraceAsString()));
	}
}
else
    echo $comicmanager->render('add_comic.twig', array('title'=>'Add comic', 'key_fields'=> metadata::$valid_key_fields));