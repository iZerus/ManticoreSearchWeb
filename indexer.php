<?php

header("Access-Control-Allow-Origin: *");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = parse_ini_file('config.ini');
if (isset($_GET['index'], $_GET['token'])) {
    $token = $_GET['token'];
    $index_table = $_GET['index'];
    if (!isset($config[$index_table]) || $token != $config[$index_table])
        die('Invalid index or token');
} else
    die('Index or token is undefined');

if (!preg_match("/^([a-zA-Z0-9]+)$/", $index_table))
    die('Invalid indexname');

$result = shell_exec("sudo ./indexer.sh $index_table");

if (strpos($result, 'successfully sent SIGHUP to searchd') !== false)
    echo 'Success';
else
    echo 'Error';