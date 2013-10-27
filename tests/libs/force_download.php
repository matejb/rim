<?php

$name = (isset($_GET['name'])) ? trim($_GET['name']) : '';
$filename = dirname(__FILE__) . '/../tests_resources/big_images/' . $name;

header('Content-Type: application/force-download');
header('Content-Disposition: attachment; filename=' . $name);
header('content-transfer-encoding: binary');

$fp = fopen($filename, 'rb');

// send the right headers
header("Content-Length: " . filesize($filename));

// dump the picture and stop the script
fpassthru($fp);
exit;