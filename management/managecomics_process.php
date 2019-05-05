<?php
/**
 * Created by PhpStorm.
 * User: Anders
 * Date: 24.12.2018
 * Time: 14.06
 */
error_reporting(E_ALL);
ini_set('display_errors', true);
if(empty($_POST))
    die('No data');

//print_r($_POST);

require 'class_management.php';
$comicmanager=new management;
$comicinfo = $comicmanager->comicinfo($_POST['comic']);

foreach($_POST['release'] as $date=>$release)
{
    if(empty($release['value']))
        continue;
    //var_dump($date);
    //var_dump($_POST['site']);
    //$release = $comicmanager->get(array('date'=>$date, 'site'=>$_POST['site']));
    $args = array('date'=>$date, 'site'=>$_POST['site']);
    $args[$_POST['mode']]=$release['value'];
    if(!empty($release['uid']))
        $args['uid'] = $release['uid'];

    $comicmanager->add_or_update($args);


}
if(!empty($_SERVER['HTTP_REFERER']))
    header('Location: '.$_SERVER['HTTP_REFERER']);
