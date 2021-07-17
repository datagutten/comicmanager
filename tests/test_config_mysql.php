<?php
$config['file_path'] = __DIR__ . '/images';

$config['comics']['site_url'] = getenv('COMICS_SITE');
$config['comics']['secret_key'] = getenv('COMICS_KEY');
$config['web_root'] = '/comicmanager';
$config['web_image_root'] = '/comicmanager/images'; //Web accessible path to image files, should point to the same folder as file_path

$config['debug'] = true;

if (!empty(getenv('DB_DATABASE')))
{
    $config['db']['db_user'] = getenv('DB_USER');
    $config['db']['db_password'] = getenv('DB_PASSWORD');
    $config['db']['db_name'] = getenv('DB_DATABASE');
} else
{
    $config['db']['db_user'] = "php_test";
    $config['db']['db_password'] = "password";
    $config['db']['db_name'] = 'comicmanager_test';
}
$config['db']['db_host'] = "localhost";
$config['db']['db_type'] = 'mysql';

$config['comics']['db'] = $config['db'];

return $config;