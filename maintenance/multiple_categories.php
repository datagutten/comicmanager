<?Php

use datagutten\comicmanager\maintenance\Maintenance;

$file = __FILE__;

/** @var Maintenance $maintenance */
$maintenance = require 'loader.php';
$output = $maintenance->multipleCategories();
echo implode("\n", $output);