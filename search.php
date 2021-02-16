<?php

header("Access-Control-Allow-Origin: *");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// Если вместо 127.0.0.1 написать localhost, то под линуксом PDO может приконнектиться к MySQL
$pdo = new PDO('mysql:host=127.0.0.1;port=9306');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$res = [];
$res['match'] = [];
$kw = $_GET['kw'];
$index_table = $_GET['index'];
$lim = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;



function _array_push(&$array, &$items) { foreach ($items as &$value) $array[] = $value; }

function matchQuery($kw) {
    global $pdo, $res, $lim, $index_table;
    $stmt = $pdo->prepare("SELECT * FROM ".$index_table." WHERE MATCH(:kw) LIMIT :limit");
    $stmt->bindParam(":kw", $kw, PDO::PARAM_STR);
    $stmt->bindParam(":limit", $lim, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll();
    _array_push($res['match'], $results);
}

// $kw = addcslashes($kw, '^|"\'!@$()-/<\\~*%');

matchQuery('^'.$kw.'');
matchQuery(''.$kw.'');
matchQuery('*'.$kw.'*');


$response = [ 'keywords' => [], 'match' => [], 'suggest' => [] ];


$id_list = [];
foreach ($res['match'] as $value) {
    if (!in_array($value['id'], $id_list)) {
        $response['match'][] = $value;
        array_push($id_list, $value['id']);
    }
}


echo json_encode($response, true);