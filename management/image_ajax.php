<?php

use datagutten\comicmanager\comicmanager;
use datagutten\comicmanager\exceptions;

require '../vendor/autoload.php';

if (empty($_GET) && !empty($argv[1]))
{
    $_GET['key'] = $argv[1];
    $_GET['key_field'] = 'id';
    $_GET['comic'] = 'pondus';
}
if (empty($_GET['key']))
    die();

$comicmanager = new comicmanager();
$comicmanager->comicinfo($_GET['comic']);
$strip = $comicmanager->strips->from_key($_GET['key'], $_GET['key_field']);
try
{
    $release = $strip->latest();
}
catch (exceptions\StripNotFound $e)
{
    die();
}
$image = $release->get_image($comicmanager);

if (!empty($_SERVER['HTTP_HOST']) && $image->url[0] == '/') //Convert relative URL to absolute
    printf('%s://%s%s', $_SERVER['REQUEST_SCHEME'], $_SERVER['HTTP_HOST'], $image->url);
else
    echo $image->url;