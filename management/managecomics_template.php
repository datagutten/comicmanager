<?php
/**
 * Created by PhpStorm.
 * User: Anders
 * Date: 24.12.2018
 * Time: 11.10
 */
require '../vendor/autoload.php';

$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader, array('debug' => true));
$twig->addExtension(new Twig_Extension_Debug());

switch($_GET['mode'])
{
    case 'id': $sortmode='id'; break;
    case 'category': $sortmode='category'; break;
    case 'original_date': $sortmode='original_date'; break;
    default: $error_text='Invalid mode: '.$_GET['mode'];
}


require 'class_management.php';
$comicmanager=new management;
$comicinfo=$comicmanager->comicinfo_get();
if($comicinfo===false)
    $error_text=$comicmanager->error;
else
{
    $site=$_GET['site'];
    //Filter by year and/or month
    if(empty($_GET['year']))
        $filter_year=false;
    else
        $filter_year=$_GET['year'];
    if(empty($_GET['month']))
        $filter_month=false;
    else
        $filter_month=$_GET['month'];

    if($_GET['source']=='comics' && is_object($comicmanager->comics)) //Fetch releases from comics
    {
        $source=sprintf('Fetching releases from %s', $comicmanager->comics->site);
        if(!empty($_GET['year']) && empty($_GET['month']))
            $releases=$comicmanager->comics->releases_year($site,$_GET['year']);
        elseif(!empty($_GET['year']) && !empty($_GET['month']))
            $releases=$comicmanager->comics->releases_month($site,$_GET['year'],$_GET['month']);
        else
            $error_text='Year and/or month must be specified'; //Filtering is required when using jodal comics

        if(isset($releases) && $releases===false)
            $error_text=$comicmanager->comics->error;
    }
    elseif($_GET['source']=='file')
    {
        $source='Fetching releases from local files';
        $releases=$comicmanager->filereleases_date($site,$filter_year,$filter_month);

        if($releases===false)
            $error_text='No file releases found: '.$comicmanager->error;
    }
    else
        $error_text=sprintf('Invalid source: %s',$_GET['source']);
}

if(!empty($error_text))
    echo $twig->render('error.html', array(
        'error'=>$error_text,
        'root'=>'..',
        'comic'=>$comicinfo['id'],
        ));
else {
    foreach ($releases as $key=>$release)
    {
        //Check if release already is in DB
        $release['site']=$_GET['site'];
        $release_db = $comicmanager->get($release);

        if(empty($release_db))
            continue;
        elseif(!empty($release_db[$sortmode])) //Already sorted
            unset($releases[$key]);
        else
            $releases[$key]+=$release_db; //Append information from DB
    }

    echo $twig->render('managecomics_id.html', array(
        'name' => 'Comics ID',
        'title' => sprintf('%s %s', $comicinfo['name'], $_GET['mode']),
        'comic' => $comicinfo['id'],
        'site' => $site,
        'source' => $source,
        'releases' => $releases,
        'mode' => $_GET['mode'],
        'root' => '..',
        'categories' => $comicmanager->categories(true),
    ));
}