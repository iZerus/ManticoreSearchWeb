<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = parse_ini_file('config.ini');

if (isset($_GET['wordforms'], $_GET['token'])) {
    $file = $_GET['wordforms'];
    $token = $_GET['token'];
    if (isset($config[$file]) && $token == $config[$file])
    {
        $data = file_get_contents(__DIR__.'/wordforms/'.$file.'.wfs');
        echo($data);
    }
    else
        die('Invalid wordforms or token');
}
else
    die('Error: wordforms or token is undefined');

?>