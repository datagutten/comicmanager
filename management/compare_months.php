<?php

use datagutten\comicmanager\web;

require '../vendor/autoload.php';
$comicmanager = new web();
$info = $comicmanager->comicinfo_get();
if(empty($info))
    die();

if(empty($_POST)) {
    echo $comicmanager->render('compare_months_front.twig', array('title'=>'Compare months', 'js' => 'compare_months.js'));
} else {
    $sites = array_combine($_POST['site'], $_POST['start']);
    $sites = array_filter($sites);
    $days = array();
    $now = time();
    try {
        for ($offset = 0; $offset <= 40; $offset++) //Loop through the days
        {
            foreach ($sites as $site => $start) {
                if ($offset === 0) {
                    $start = strtotime($start);
                    if ($start === false)
                        throw new Exception('Invalid start time: ' . $start);
                    $sites[$site] = $start;
                }

                if ($start + $offset * 86400 > $now)
                    break;
                $date = date('Ymd', $start + $offset * 86400);
                $release = $comicmanager->get(array('date' => $date, 'site' => $site));
                if (!empty($release[$info['keyfield']]))
                    $days[$offset]['key'] = $release[$info['keyfield']];
                if (empty($release)) {
                    $release = array('date' => $date, 'site' => $site);
                    try {
                        $release['file'] = $comicmanager->imagefile($release);
                    } catch (Exception $e) {
                        $release = null;
                    }
                } elseif(!empty($release[$info['keyfield']]))
                    $release['key'] = $release[$info['keyfield']];
                $days[$offset]['releases'][$site] = $release;
                //var_dump($days[$offset][$site]);
            }
        }

        echo $comicmanager->render('compare_months.twig', array('days' => $days, 'extra_css' => 'compare_months.css', 'js' => 'compare_months.js', 'mode' => 'compare'));
    }
    catch (Exception $e)
    {
        echo $comicmanager->render('error.twig', array('error'=>$e->getMessage()));
    }
}