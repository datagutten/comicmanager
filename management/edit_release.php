<?php
/**
 * Created by PhpStorm.
 * User: Anders
 * Date: 05.05.2019
 * Time: 21.33
 */

use datagutten\comicmanager\exceptions;
use datagutten\comicmanager\web;

require '../vendor/autoload.php';
$comic_manager = new web;
$comic = $comic_manager->comicinfo_get();
if ($comic === false)
    die();
$errors = '';

if (!empty($_POST))
{
    $releases = $_POST['release'];
    foreach ($releases as $release)
    {
        if (isset($release['value']))
        {
            $category = $release['value'];
            if (empty($category))
                $category = null;
            continue;
        }
        if (empty($release['category']))
            $release['category'] = $category ?? null;

        try
        {
            $comic_manager->releases->save($release);
        }
        catch (Exception $e)
        {
            die($comic_manager->render_exception($e));
        }

        $release_obj = $comic_manager->releases->get(['uid' => $release['uid']], false);
        foreach ($release as $key => $value)
        {
            if (empty($value))
                $value = null;
            $release_obj->$key = $value;
        }
        $release_obj->save(allow_insert: false, update_empty: true);
    }
}
if (!empty($_GET['key_field']) && !empty($_GET['key']))
{
    $strip = $comic_manager->strips->from_key($_GET['key'], $_GET['key_field']);
    try
    {
        $releases = $strip->releases();
        $context = [
            'title' => 'Edit release',
            'releases' => $strip->releases(),
            'first_release' => $strip->latest(),
            'errors' => $errors,
            'comic' => $comic,
        ];
        if ($comic->has_categories)
            $context['categories'] = $comic->categories(false, true);
        echo $comic_manager->render('edit_release.twig', $context);
    }
    catch (exceptions\StripNotFound $e)
    {
        die($comic_manager->render_error(sprintf('No release found with %s %s', $_GET['keyfield'], $_GET['key'])));
    }
    catch (exceptions\ComicInvalidArgumentException|exceptions\DatabaseException $e)
    {
        die($comic_manager->render_exception($e));
    }
} else
{
    $comic->possible_key_fields[] = 'uid'; //Append uid to key field list
    echo $comic_manager->render('select_key.twig', array(
        'title' => 'Edit release'));
}