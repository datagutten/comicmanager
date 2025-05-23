<?php
/**
 * Created by PhpStorm.
 * User: Anders
 * Date: 24.12.2018
 * Time: 11.10
 */

use datagutten\comicmanager\elements\Release;
use datagutten\comicmanager\web;
use datagutten\comicmanager\exceptions;

switch ($_GET['mode'])
{
    case 'id':
        $sortmode = 'id';
        $image_class = 'comic_full_size';
        break;
    case 'category':
        $sortmode = 'category';
        $image_class = 'comic';
        break;
    case 'original_date':
        $sortmode = 'original_date';
        $image_class = 'comic_full_size';
        break;
    default:
        $error_text = 'Invalid mode: ' . $_GET['mode'];
}


require '../vendor/autoload.php';
$comicmanager=new web;
$comicinfo=$comicmanager->comicinfo_get();

if($comicinfo===false)
    die();
else
{
    if(!empty($_GET['custom_site']))
        $_GET['site'] = $_GET['custom_site'];
    if(empty($_GET['site']) || empty($_GET['source'])) {
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
        $source=sprintf('Fetching releases from %s', $comicmanager->comics->site_url);
        try {
            if (!empty($_GET['year']) && empty($_GET['month']))
                $releases = $comicmanager->comics->releases_year($site, $_GET['year']);
            elseif (!empty($_GET['year']) && !empty($_GET['month']))
                $releases = $comicmanager->comics->releases_month($site, $_GET['year'], $_GET['month']);
            else
                die($comicmanager->render_error('Year and/or month must be specified')); //Filtering is required when using jodal comics
            foreach($releases as $key=>$release)
            {
                $releases[$key] = Release::from_comics($comicmanager, $release, $site);
            }
        }
        catch (Exception $e) {
            die($comicmanager->render_exception($e));
        }
    }
    elseif($_GET['source']=='file')
    {
        $source='Fetching releases from local files';
        try {
            $releases=$comicmanager->files->releases_file_date($site,$filter_year,$filter_month);
            foreach($releases as $key=>$release)
            {
                $releases[$key] = new Release($comicmanager, [
                    'site' => $site,
                    'date' => $release['date'],
                    'image_file' => $release['image_file'],
                    'title' => $release['title'],
                ]);
            }
        }
        catch (Exception $e) {
        }

        if(empty($releases))
            $error_text='No file releases found'; // TODO: Check if this can be empty
    }
    else
        die($comicmanager->render_error(sprintf('Invalid source: %s',$_GET['source'])));
}

if(!empty($error_text))
{
    echo $comicmanager->render('error.twig', array(
        'title' => 'Error',
        'error' => $error_text,
        'root' => $comicmanager->root,
        'comic' => $comicinfo,
    ));
}
elseif(!empty($e))
{
    echo $comicmanager->render('exception.twig', array(
        'title' => 'Error',
        'e' => $e,
        'comic' => $comicinfo,
    ));
}
else {
    foreach ($releases as $key=>$release)
    {
        //Check if release already is in DB
        try
        {
            $release->load_db();
        }
        catch (exceptions\ReleaseNotFound $e)
        {
            continue;
        }
        $release->site = $_GET['site'];

        if(!empty($release->$sortmode)) //Already sorted
            unset($releases[$key]);
    }
    if($_GET['mode']=='original_date')
        $js = 'release_date.js';
    else
        $js = 'id_link.js';

    if($comicinfo->has_categories)
        $categories = $comicmanager->info->categories(true, true);
    else
        $categories = [];

    echo $comicmanager->render('manage_comics.twig', array(
        'name' => 'Comics ID',
        'title' => sprintf('%s %s', $comicinfo['name'], $_GET['mode']),
        'comic' => $comicinfo,
        'site' => $site,
        'source' => $source,
        'releases' => $releases,
        'mode' => $_GET['mode'],
        'image_class' => $image_class,
        'root' => $comicmanager->root,
        'categories' => $categories,
        'js' => $js,
    ));
}