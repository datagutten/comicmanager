<?php

use datagutten\comicmanager\web;

require '../vendor/autoload.php';
$comicmanager = new web();

$comicinfo = $comicmanager->comicinfo_get();

if($comicinfo['has_categories']==1) {
    header('Location: edit_categories.php?comic=' . $_GET['comic']);
    die();
}

if(!empty($_POST['enable_'.$comicinfo['id']]))
{
    $comicinfo->enableCategories();
    header('Location: edit_categories.php?comic='.$comicinfo['id']);
}
else
    echo $comicmanager->render('enable_categories.twig', ['title'=>'Enable categories']);