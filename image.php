<?php
if(!empty($_GET['uid']) || !empty($_GET['release']))
{
    require 'class.php';
    $comic_manager=new comicmanager;
    if(empty($_GET['release']) && !empty($_GET['uid']))
        $release = $comic_manager->get(array('uid'=>$_GET['uid']));
    else
        $release = json_decode($_GET['release'], true);
    try {
        $_GET['file'] = $comic_manager->imagefile($release);
    }
    catch (Exception $e) {
        echo $e->getMessage();
    }

}
if(isset($_GET['file'])) {
    $size = getimagesize($_GET['file']);
    header("Content-type: {$size['mime']}");

    $fp = fopen($_GET['file'], 'r');
    fpassthru($fp);
    fclose($fp);
}