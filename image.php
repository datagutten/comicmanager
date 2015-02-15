<?php
$size = getimagesize($_GET['file']);
//header('Content-type: image/png');
header("Content-type: {$size['mime']}");

$fp=fopen($_GET['file'],'r');
fpassthru($fp);
fclose($fp);
?>