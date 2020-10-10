<?php
//echo "Test config loaded\n";

$config['file_path'] = __DIR__ . '/images';

$config['comics']['site_url'] = getenv('comics_site');
$config['comics']['secret_key'] = getenv('comics_key');
$config['debug'] = true;



return $config;