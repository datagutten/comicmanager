<?php

use datagutten\comicmanager\setup;

require '../vendor/autoload.php';
$comicmanager = new comicmanager();
$comicinfo = $comicmanager->comicinfo_get();
if($comicinfo['has_categories']==1) {
    header('Location: edit_categories.php?comic=' . $_GET['comic']);
    die();
}

$setup = new setup();

if(!empty($_POST['enable_'.$comicinfo['id']]))
{
    $setup->enableCategories($comicinfo['id']);
    header('Location: edit_categories.php?comic='.$comicinfo['id']);
}
else
    echo $comicmanager->render('enable_categories.twig', ['title'=>'Enable categories']);