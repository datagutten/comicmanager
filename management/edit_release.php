<?php
/**
 * Created by PhpStorm.
 * User: Anders
 * Date: 05.05.2019
 * Time: 21.33
 */

use datagutten\comicmanager\web;
require '../vendor/autoload.php';
$comic_manager=new web;
$comic_info=$comic_manager->comicinfo_get();
if($comic_info===false)
    die();
$errors='';

if(!empty($_POST))
{
    //print_r($_POST);
    $releases = $_POST['release'];
    foreach ($releases as $release) {
        if(isset($release['value'])) {
            $category = $release['value'];
            if(empty($category))
                $category = null;
            //var_dump($category);
            continue;
        }
        if(empty($release['category']))
            $release['category'] = $category;

        try {
            //print_r($release);
            $comic_manager->add_or_update($release);
        }
        catch (Exception $e)
        {
            $errors.=$e->getMessage()."\n";
        }
    }
}
if(!empty($_GET['keyfield']) && !empty($_GET['key'])) {
    $release = $comic_manager->get(array($_GET['keyfield'] => $_GET['key']), true);

    $release->setFetchMode(PDO::FETCH_ASSOC);

    echo $comic_manager->render('edit_release.twig',
        array('title' => 'Edit release',
            'releases' => $release,
            'categories' => $comic_manager->categories(false, true),
            'errors' => $errors,
            'js'=>'edit_release.js'));
}
else
{
    $comic_info['possible_key_fields'][] = 'uid';
    echo $comic_manager->render('select_key.twig', array(
        'key_fields'=>$comic_info['possible_key_fields'],
        'title'=>'Edit release'));
}