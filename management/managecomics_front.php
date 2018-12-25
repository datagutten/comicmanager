<?php
/**
 * Created by PhpStorm.
 * User: Anders
 * Date: 24.12.2018
 * Time: 22.05
 */
require 'class_management.php';
$comic_manager=new management;
$comic_info=$comic_manager->comicinfo_get();
if($comic_info===false)
    $error_text=$comic_manager->error;
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
        'root'=>$comic_manager->root,
        'title'=>'Manage comics',
        'comic'=>$comic_info,
        'actions'=>$actions,
        'sites'=>$comic_manager->sites(),
        'sources'=>$comic_manager->sources,
        );
    echo $comic_manager->twig->render('manage_comics_front.twig', $context);
}