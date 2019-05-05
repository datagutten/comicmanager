<?php
/**
 * Created by PhpStorm.
 * User: Anders
 * Date: 24.12.2018
 * Time: 11.10
 */

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
    die();
else
{
    if(empty($_GET['site'])) {
        header('Location: managecomics_front.php?comic=' . $comicinfo['id']);
        die();
    }
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
        try {
            $releases=$comicmanager->releases_file_date($site,$filter_year,$filter_month);
        }
        catch (Exception $e) {
            $error_text=$e->getMessage();
        }

        if(empty($releases))
            $error_text='No file releases found: '.$comicmanager->error;
    }
    else
        $error_text=sprintf('Invalid source: %s',$_GET['source']);
}

if(!empty($error_text))
    echo $comicmanager->twig->render('error.twig', array(
        'title'=>'Error',
        'error'=>$error_text,
        'root'=>$comicmanager->root,
        'comic'=>$comicinfo,
        ));
else {
    foreach ($releases as $key=>&$release)
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

    echo $comicmanager->twig->render('manage_comics.twig', array(
        'name' => 'Comics ID',
        'title' => sprintf('%s %s', $comicinfo['name'], $_GET['mode']),
        'comic' => $comicinfo,
        'site' => $site,
        'source' => $source,
        'releases' => $releases,
        'mode' => $_GET['mode'],
        'root' => $comicmanager->root,
        'categories' => $comicmanager->categories(true, true)->fetchAll(),
        'js' => 'release_date.js',
    ));
}