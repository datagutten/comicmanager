<?php
if(!empty($_GET['uid']) || !empty($_GET['release']))
{
    require 'vendor/autoload.php';
    $comic_manager=new comicmanager;
    if(empty($_GET['release']) && !empty($_GET['uid']))
        $release = $comic_manager->get(array('uid'=>$_GET['uid']));
    else
        $release = json_decode($_GET['release'], true);
    try {
        $_GET['file'] = $comic_manager->imagefile($release);
    }
    catch (Exception $e) {
        image_error($e->getMessage());
        error_log($e);
    }
}
if(isset($_GET['file'])) {
    $size = getimagesize($_GET['file']);
    set_error_handler('handler');
    header("Content-type: {$size['mime']}");

    $fp = fopen($_GET['file'], 'r');
    if($fp!==false)
    {
        fpassthru($fp);
        fclose($fp);
    }
}

function image_error($string)
{
    $im=imagecreatetruecolor(1000,20);
    imagefill($im, 0,0, imagecolorallocate($im, 255,255,255));
    imagestring($im, 4, 0,0, $string, imagecolorallocate($im, 255,0,0));
    header("Content-type: image/png");
    imagepng($im);
}

function handler(/** @noinspection PhpUnusedParameterInspection */ $errno, $errstr)
{
    image_error($errstr);
}