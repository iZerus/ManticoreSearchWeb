<?php

header("Access-Control-Allow-Origin: *");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$token = $_GET['token'];
$index_table = $_GET['index'];

$config = parse_ini_file('config.ini');
$_token = isset($config['token']) ? $config['token'] : '123456';

if ($token != $_token)
    die('Invalid token');

if (!preg_match("/^([a-zA-Z0-9]+)$/", $index_table))
    die('Invalid indexname');



echo 200;