<?php
/**
 * Created by PhpStorm.
 * User: Anders
 * Date: 25.12.2018
 * Time: 17.53
 */

require 'class.php';
$comic_manager = new comicmanager;
$comic_info = $comic_manager->comicinfo_get();
if (empty($comic_info))
    die();

if (!isset($_GET['comic'])) {
    echo $comic_manager->twig->render('showcomics_front.twig', array(
        'title' => 'Show ' . $comic_info['name'],
        'comic' => $comic_info,
        'root' => $comic_manager->root,
        'sites' => $comic_manager->sites(),
        'extra_css' => 'menu.css',
        'categories' => $comic_manager->categories(),
        'range' => $comic_manager->db->query(
            $q = sprintf('SELECT MIN(%1$s) AS min, MAX(%1$s) AS max FROM %2$s',
                $comic_info['keyfield'],
                $comic_info['id']), 'assoc'),

    ));
}
else {

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
        $query = sprintf('SELECT * FROM %s WHERE %s=?', $comic_info['id'], $key_field);
        $where = sprintf('%s=?', $key_field);
        $st = $comic_manager->db->prepare($query);
        $values = array($_GET['key_from']);
        $releases = $comic_manager->db->execute($st, array($_GET['key_from']), 'all');
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
        $title = $comic_manager->db->query(sprintf('SELECT name FROM %s_categories WHERE id=%d',
            $comic_info['id'], $_GET['category']), 'column');
    } else {
        header('Location: showcomics_front.php?comic=' . $comic_info['id']);
        die();
    }


    if (!empty($where)) {
        $q = sprintf('SELECT * FROM %s WHERE %s', $comic_info['id'], $where);
        $st = $comic_manager->db->prepare($q);
        $releases = $comic_manager->db->execute($st, $values, 'all');
    }

    if (empty($releases)) {
        $q_debug = str_replace('?', '%s', $q);
        $q_debug = vsprintf($q_debug, $values);
        echo $comic_manager->twig->render('error.twig', array(
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

        echo $comic_manager->twig->render('showcomics.twig', array(
            'title' => $title,
            'root' => $comic_manager->root,
            'comic' => $comic_info,
            'releases' => $releases_show,
            'key_field' => $key_field,
            'mode' => 'category',
        ));
    }
}