<?php
/**
 * Created by PhpStorm.
 * User: Anders
 * Date: 25.12.2018
 * Time: 15.28
 */
require 'vendor/autoload.php';
$comic_manager = new comicmanager;
$comic_info = $comic_manager->comicinfo_get();
if (empty($comic_info))
    die();

echo $comic_manager->render('showcomics_front.twig', array(
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