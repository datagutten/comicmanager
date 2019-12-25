<?php
/**
 * Created by PhpStorm.
 * User: Anders
 * Date: 24.12.2018
 * Time: 22.05
 */

use datagutten\comicmanager\web;

require '../vendor/autoload.php';
$comic_manager=new web;
$comic_info=$comic_manager->comicinfo_get();
if (empty($comic_info))
    die();
else
{
    $actions = array();
    //Build a list of possible actions
    if($comic_info['has_categories']==1)
        $actions['category']='Category';
    if(array_search('id',$comic_info['possible_key_fields'])!==false)
        $actions['id']='ID';
    if(array_search('original_date',$comic_info['possible_key_fields'])!==false)
        $actions['original_date']='Original published date';

    $sources=array('file'=>'Local files');

    $context = array(
        'root' => $comic_manager->root,
        'title' => 'Manage comics',
        'comic' => $comic_info,
        'actions' => $actions,
        'sources' => $comic_manager->sources,
    );

    try
    {
        $context['sites'] = $comic_manager->sites();
        echo $comic_manager->render('manage_comics_front.twig', $context);
    }
    catch (Exception $e)
    {
        $context['e'] = $e;
        echo $comic_manager->render('exception.twig', $context);
    }
}