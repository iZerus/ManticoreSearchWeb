<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Если вместо 127.0.0.1 написать localhost, то под линуксом PDO может приконнектиться к MySQL
$pdo = new PDO('mysql:host=127.0.0.1;port=9306');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$res = [];
$kw = $_GET['kw'];


$stmt = $pdo->query("SELECT * FROM iproducts WHERE MATCH('$kw')");
$results = $stmt->fetchAll();
$res['match'] = $results;


$stmt = $pdo->query("CALL KEYWORDS('*$kw*', 'iproducts')");
$results = $stmt->fetchAll();
$res['keywords'] = [];
foreach ($results as $value)
    if (substr($value['normalized'], 0, 1) == '=') {
        $key = explode("=", $value['normalized'])[1];
        $stmt = $pdo->query("SELECT * FROM iproducts WHERE MATCH('$key')");
        $key_results = $stmt->fetchAll();
        $res['keywords'][] = $key_results;
    }


$stmt = $pdo->query("CALL SUGGEST('$kw', 'iproducts')");
$results = $stmt->fetchAll();
$res['suggest'] = [];
if (count($results) > 1) {
    foreach ($results as $value) {
        $key = $value['suggest'];
        $stmt = $pdo->query("SELECT * FROM iproducts WHERE MATCH('$key')");
        $key_results = $stmt->fetchAll();
        $res['suggest'][] = $key_results;
    }
}


$response = [ 'keywords' => [], 'match' => [], 'suggest' => [] ];
function clrDplcts(&$res, &$response, $field) {
    $tmp = [];
    foreach ($res[$field] as $array)
        foreach ($array as $value)
            $tmp[$value['id']] = $value;

    $response[$field] = [];
    foreach ($tmp as $key => $value)
        $response[$field][$key] = $value;
}
clrDplcts($res, $response, 'keywords');
clrDplcts($res, $response, 'suggest');
foreach ($res['match'] as $value)
    $response['match'][$value['id']] = $value;

echo json_encode($response, true);