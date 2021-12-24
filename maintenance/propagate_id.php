<?php

use datagutten\comicmanager\maintenance\Maintenance;

$file = __FILE__;

/** @var Maintenance $maintenance */
$maintenance = require 'loader.php';
$output = $maintenance->propagateId();
echo implode("\n", $output);
