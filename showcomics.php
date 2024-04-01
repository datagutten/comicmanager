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
            die($comic_manager->render_exception($e));
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
        {
            $show_duplicates = false;
            $strips = [$comic_manager->strip_from_key($_GET['key_from'])];
        }
        else
            $strips = $comic_manager->strip_range($_GET['key_from'], $_GET['key_to']);
    } elseif (!empty($_GET['site']) && !empty($_GET['date'])) //Specific release
    {
        try
        {
            if (str_contains($_GET['date'], '%') || str_contains($_GET['site'],'%'))
                $releases = $comic_manager->releases->wildcard($_GET['site'], $_GET['date']);
            else
                $releases = [Release::from_date($comic_manager, $_GET['site'], $_GET['date'])];
        }
        catch (exceptions\comicManagerException $e)
        {
            die($comic_manager->render_exception($e));
        }
    }
    elseif (!empty($_GET['category']))
    {
        $releases = $comic_manager->releases->category(intval($_GET['category']));
        $show_newest = true;
        $title = $comic->categoryName((int)$_GET['category']);
        $show_duplicates = false;
    }
    elseif(!empty($_GET['list']))
    {
        $releases = $comic_manager->lists->parse_list_file($comic->id, $_GET['list'], $_GET['list_folder'] ?? null);
        $title = basename($_GET['list'], '.txt');
    }
    else
    {
        if ($comic->has_categories)
            $categories = $comic->categories();
        else
            $categories = [];

        list($lists, $folders) = $comic_manager->lists->lists($comic->id, $_GET['list_folder'] ?? null);
        echo $comic_manager->render('showcomics_front.twig', array(
            'title' => 'Show ' . $comic['name'],
            'sites' => $comic->sites(),
            'extra_css' => 'menu.css',
            'categories' => $categories,
            'range' => $comic_manager->strips->key_high_low(),
            'lists' => $lists,
            'list_folders' => $folders,
            'list_folder' => $_GET['list_folder'] ?? null,
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
                try
                {
                    if (!$show_duplicates)
                        $releases[] = $strip->latest();
                    else
                        $releases = array_merge($releases, $strip->releases());
                }
                catch (exceptions\StripNotFound $e)
                {
                    continue;
                }
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