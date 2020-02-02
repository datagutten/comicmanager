<?php
/**
 * Created by PhpStorm.
 * User: Anders
 * Date: 24.12.2018
 * Time: 14.06
 */

use datagutten\comicmanager\web;

if(empty($_POST))
    die('No data');

//print_r($_POST);

require '../vendor/autoload.php';
$comicmanager=new web;
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

    try {
        $comicmanager->add_or_update($args, 'key');
    }
    catch (Exception $e)
    {
        die($comicmanager->render('exception.twig', array('e'=>$e)));
    }
}
if(!empty($_SERVER['HTTP_REFERER']))
    header('Location: '.$_SERVER['HTTP_REFERER']);
