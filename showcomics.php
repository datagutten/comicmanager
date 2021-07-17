<?php
/**
 * Created by PhpStorm.
 * User: Anders
 * Date: 25.12.2018
 * Time: 17.53
 */

use datagutten\comicmanager\elements\Release;
use datagutten\comicmanager\web;
use datagutten\comicmanager\exceptions;

require 'vendor/autoload.php';
$comic_manager = new web;
$comic = $comic_manager->comicinfo_get();
if (!empty($comic))
{

    /*Rekkefølge på parametere:
    comic
    view
    [keyfield]
    [site]
    value
    */
    $title = sprintf('Show %s', $comic->name);

    if (isset($_GET['key_field']))
    {
        try
        {
            $comic->setKeyField($_GET['key_field']);
        }
        catch (InvalidArgumentException $e)
        {
            die($e->getMessage()); //TODO: Use error template
        }
    }

    /**
     * @var bool Show multiple releases for the same strip
     */
    $show_duplicates = true;
    if (!empty($_GET['key']))
        $strips = [$comic_manager->strip_from_key($_GET['key'])];
    elseif (!empty($_GET['key_from']))
    {
        if (empty($_GET['key_to']))
            $strips = [$comic_manager->strip_from_key($_GET['key_from'])];
        else
            $strips = $comic_manager->strip_range($_GET['key_from'], $_GET['key_to']);
    } elseif (!empty($_GET['site']) && !empty($_GET['date'])) //Specific release
    {
        if (strpos($_GET['date'], '%') !== false)
        {
            $releases = $comic_manager->releases_date_wildcard($_GET['site'], $_GET['date']);
        } else
        {
            try
            {
                $releases = [Release::from_date($comic_manager, $_GET['site'], $_GET['date'])];
            }
            catch (exceptions\comicManagerException $e)
            {
                die($comic_manager->render('exception.twig', ['e' => $e]));
            }
        }
    } elseif (!empty($_GET['category']))
    {
        $releases = $comic_manager->releases_category(intval($_GET['category']));
        $show_newest = true;
        $title = $comic_manager->category_name((int)$_GET['category']);
        $show_duplicates = false;
    } else
    {
        echo $comic_manager->render('showcomics_front.twig', array(
            'title' => 'Show ' . $comic['name'],
            'comic' => $comic,
            'root' => $comic_manager->root,
            'sites' => $comic_manager->sites(),
            'extra_css' => 'menu.css',
            'categories' => $comic_manager->categories(),
            'range' => $comic_manager->key_high_low($comic['key_field']),
        ));
        die();
    }

    if (empty($releases))
    {
        $releases = [];
        if (!empty($strips))
        {
            foreach ($strips as $strip)
            {
                if (!$show_duplicates)
                    $releases[] = $strip->latest();
                else
                    $releases = array_merge($releases, $strip->releases());
            }
        }
    }

    echo $comic_manager->render('showcomics.twig', array(
        'title' => $title,
        'root' => $comic_manager->root,
        'comic' => $comic,
        'releases' => $releases,
        'key_field' => $comic_manager->info->key_field,
        'mode' => 'category',
    ));
}