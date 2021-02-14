<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$config = parse_ini_file('config.ini');

// Если вместо 127.0.0.1 написать localhost, то под линуксом PDO может приконнектиться к MySQL
$pdo = new PDO('mysql:host=127.0.0.1;port='.$config['port']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$res = [];
$res['match'] = [];
$kw = $_GET['kw'];
$lim = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;



function _array_push(&$array, &$items) { foreach ($items as &$value) $array[] = $value; }

function matchQuery($match) {
    global $pdo, $config, $res, $lim;
    $stmt = $pdo->query("SELECT * FROM ".$config['index_table']." WHERE $match LIMIT $lim");
    $results = $stmt->fetchAll();
    _array_push($res['match'], $results);
}


matchQuery("MATCH('^$kw')");
matchQuery("MATCH('$kw')");
matchQuery("MATCH('*$kw*')");


$response = [ 'keywords' => [], 'match' => [], 'suggest' => [] ];


$id_list = [];
foreach ($res['match'] as $value) {
    if (!in_array($value['id'], $id_list)) {
        $response['match'][] = $value;
        array_push($id_list, $value['id']);
    }
}


echo json_encode($response, true);