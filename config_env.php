<?php
$config['file_path'] = getenv('COMIC_FILE_PATH') ?: '/home/comics';
$config['list_path'] = getenv('COMIC_LIST_PATH') ?: '/home/lists';
$config['web_root'] = getenv('WEB_ROOT') ?: '';
$config['web_image_root'] = '/images'; //Web accessible path to image files, should point to the same folder as file_path

if (!empty(getenv('COMICS_URL')))
{
    $config['comics']['site_url'] = getenv('COMICS_URL');
    $config['comics']['secret_key'] = getenv('COMICS_KEY');
}


$config['db']['db_host'] = getenv('SQL_HOST');
$config['db']['db_user'] = getenv('SQL_USER');
$config['db']['db_password'] = getenv('SQL_PASSWORD');
$config['db']['db_name'] = getenv('SQL_DATABASE');

$config['debug'] = true;

return $config;