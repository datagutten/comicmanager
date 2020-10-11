<?php
/**
 * Created by PhpStorm.
 * User: Anders
 * Date: 25.12.2018
 * Time: 17.53
 */

use datagutten\comicmanager\web;

require 'vendor/autoload.php';
$comic_manager = new web;
$comic_info = $comic_manager->comicinfo_get();
if (!empty($comic_info)) {
    /*Rekkefølge på parametere:
    comic
    view
    [keyfield]
    [site]
    value
    */
    $title = sprintf('Show %s', $comic_info['name']);
    if (isset($_GET['key_field'])) {
        if (array_search($_GET['key_field'], $comic_info['possible_key_fields']) === false)
            die('Invalid key field');
        else
            $key_field = $_GET['key_field'];
    } else
        $key_field = $comic_info['keyfield'];

    if (!empty($_GET['key_from']) && empty($_GET['key_to'])) //Show single strip by key
    {
        $st_releases = $comic_manager->get([$key_field=>$_GET['key_from']], true);
        $releases = $st_releases->fetchAll(PDO::FETCH_ASSOC);
        $view = 'single';

    } elseif (!empty($_GET['key_from']) && !empty($_GET['key_to'])) //Show strip range
    {
        //$query=sprintf('SELECT * FROM %s ')
        $where = sprintf('%1$s>=? AND %1$s<=? GROUP BY %1$s ORDER BY %1$s', $key_field);
        $values = array($_GET['key_from'], $_GET['key_to']);
        $show_newest = true;
    } elseif (!empty($_GET['site']) && !empty($_GET['date'])) {
        $where = sprintf('date LIKE ? AND site=? GROUP BY %s ORDER BY date', $key_field);
        $values = array($_GET['date'], $_GET['site']);
        $show_newest = false; //Show correct release
    } elseif (!empty($_GET['category'])) {
        $where = 'category=?'; //GROUP BY %1$s ORDER BY %1$s
        $values = array($_GET['category']);
        $show_newest = true;
        $title = $comic_manager->category_name((int)$_GET['category']);
    } else {
        echo $comic_manager->render('showcomics_front.twig', array(
            'title' => 'Show ' . $comic_info['name'],
            'comic' => $comic_info,
            'root' => $comic_manager->root,
            'sites' => $comic_manager->sites(),
            'extra_css' => 'menu.css',
            'categories' => $comic_manager->categories(),
            'range' => $comic_manager->key_high_low($comic_info['keyfield']),
        ));
        die();
    }


    if (!empty($where)) {
        $q = sprintf('SELECT * FROM %s WHERE %s', $comic_info['id'], $where);
        $st_releases = $comic_manager->db->prepare($q);
        $st_releases->execute($values);
        $releases = $st_releases->fetchAll(PDO::FETCH_ASSOC);
    }

    if (empty($releases)) {
        $q_debug = str_replace('?', '%s', $q);
        $q_debug = vsprintf($q_debug, $values);
        echo $comic_manager->render('error.twig', array(
            'title' => $title,
            'root' => $comic_manager->root,
            'comic' => $comic_info,
            'error' => "No releases found\n$q_debug"
        ));
    } else {
        if (!empty($show_newest)) {
            $displayed_releases = array();
            $releases_show = array();
            foreach ($releases as &$release) {
                if(isset($releases_show[$release[$key_field]]))
                    continue;
                $releases_show[$release[$key_field]] = $comic_manager->get_newest($release);
                //$displayed_releases[] = $release[$key_field];
            }
        }
        else
            $releases_show = $releases;

        echo $comic_manager->render('showcomics.twig', array(
            'title' => $title,
            'root' => $comic_manager->root,
            'comic' => $comic_info,
            'releases' => $releases_show,
            'key_field' => $key_field,
            'mode' => 'category',
        ));
    }
}