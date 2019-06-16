<?php
/**
 * Called by add_key.js from compare_months.php
 */
require '../vendor/autoload.php';
$comicmanager=new comicmanager();
if(isset($_GET['date']) and isset($_GET['site']))
    $release = array('date'=>$_GET['date'], 'site'=>$_GET['site']);
elseif(!empty($_GET['uid']))
    $release = array('uid'=>$_GET['uid']);
else
    die();

try {
    $info = $comicmanager->comicinfo($_GET['comic']);
    $release_key = $comicmanager->get(array($info['keyfield']=>$_GET['key']));
    //Add key
    $release[$info['keyfield']] = $_GET['key'];
    $comicmanager->add_or_update($release);
    $release_add = $comicmanager->get($release);
    if(empty($release_add))
        $release_add = $release;
    $message = sprintf('Added %s %s to %s %s with uid %d', $release_add['site'], $release_add['date'], $info['keyfield'], $_GET['key'], $release_add['uid']);

    echo json_encode(array('comic'=>$info, 'message'=>$message, 'release'=>$release_add));
}
catch (InvalidArgumentException|Exception $e)
{
    echo json_encode(
                    array('comic'=>
                      array('id'=>$_GET['comic'],
                            'message'=>$e->getMessage(),
                            'release'=>$release
                            )
                    )
                      );
}

