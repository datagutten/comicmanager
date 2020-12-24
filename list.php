<!doctype html>
<?php

use datagutten\comicmanager\web;

require 'vendor/autoload.php';
$comic_manager=new web;
$list_path="/home/Dropbox/Tegneserier/lister/pondus";

if(!isset($_GET['list']))
{
	$lists=glob($list_path.'/*.txt');
	$lists = array_map('basename', $lists);
	echo $comic_manager->render('comic_list_select.twig', array(
        'title'=>'List comics',
        'lists'=>$lists,
        'root'=>$comic_manager->root));
}
else
{

	$list=$_GET['list'];
	$data=trim(file_get_contents($list_path.'/'.$list));
	$strips=explode("\r\n",$data);
    try {
        $filter=array_keys($comic_manager->comic_list()); //Get valid comics
    }
    catch (Exception $e)
    {
        die($comic_manager->render('error.twig', array('error'=>$e->getMessage())));
    }

	if(isset($_GET['filter']))
		$filter=explode(',',$_GET['filter']);

	if(isset($reverse))
	{
		$strips=array_reverse($strips,true);
		foreach($strips as $strip)
		{
			if(strpos($strip,':')) //Find the comic id
			{
				$comic_id=substr($strip,0,-1);
				break;
			}
		}
	}
    $set_id = 0;
	foreach ($strips as $line_num=>$strip)
	{
		if(substr($strip,0,2)=='xx' || substr($strip,0,1)=='#' || empty(trim($strip))) //Comment line
			continue;
		if(strpos($strip,'#')!==false)
			$strip=trim(preg_replace('/(.+)#.*/','$1',$strip)); //Remove comments on the end of the line

		if(strpos($strip,':')) //Lines ending with : defines the comic id
		{
			$comic_id=substr($strip,0,-1);
			if(array_search($comic_id, $filter)===false)
			    continue;

			try {
                $comic = $comic_manager->comicinfo($comic_id);
                $set_id = $line_num;
                $releases[$set_id]['comic']=$comic;
                $releases[$set_id]['releases'] = array();
            }
			catch (Exception $e)
            {
                $releases[$comic_id]['error'] = $e->getMessage();
                continue;
            }
		}
		else //Release line
        {
            if(empty($comic_id))
                die($comic_manager->render('error.twig', array(
                        'error'=>sprintf("No comic specified before line %s", $strip),
                        'title'=>'Missing comic')));

            if (array_search($comic_id, $filter) === false)
                continue;

            if (preg_match('^([0-9]{8})(?:\s+-\s+[0-9]+)*\s+-\s+([a-z]+)^', $strip, $date_and_site)) //date and site
            {
                $release_temp = array('date' => $date_and_site[1], 'site' => $date_and_site[2]);
            } else //Treat the line as primary key for the comic
            {
                $release_temp = array($comic['keyfield']=>$strip);
                //$release_temp[$comic['keyfield']] = $strip;
            }
            try {
                unset($release);
                $release = $comic_manager->get($release_temp);
            }
            catch (Exception $e)
            {
                $error_text = sprintf('Error showing line %d parsed as %s: %s', $line_num, print_r($release_temp, true), $e->getMessage());
                $releases[$set_id]['releases'][]['error'] = $error_text;
                //$dom->createElement_simple('p', $body, array('class' => 'error'), $error_text);
                unset($release);
                continue;
            }

            if (empty($release))
                $releases[$set_id]['releases'][]['error'] = "Not found: $comic_id $strip";
            else {
                $releases[$set_id]['releases'][]=$release;

            }
        }
	}

    echo $comic_manager->render('comic_list.twig', array('comics'=>$releases, 'title'=>$list));
}