<?php
$config['file_path'] = __DIR__ . '/images';

$config['comics']['site_url'] = getenv('COMICS_SITE');
$config['comics']['secret_key'] = getenv('COMICS_KEY');
$config['debug'] = true;

$config['db']['db_user'] = "php_test";
$config['db']['db_password'] = "password";
$config['db']['db_name'] = 'comicmanager_test';
$config['db']['db_type'] = 'sqlite';
$config['db']['db_file'] = sys_get_temp_dir() . '/comicmanager_test.db';

$config['comics']['db'] = $config['db'];

return $config;