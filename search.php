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


if (!preg_match("/^([a-zA-Z0-9]+)$/", $index_table))
    die('Invalid indexname');


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


function getMatch($kw) {
    matchQuery('^'.$kw.'');
    matchQuery(''.$kw.'');
    matchQuery('*'.$kw.'*');
}


function getSuggests($kw, $max_distance, $limit) {
    global $pdo, $index_table;
    $stmt = $pdo->prepare("CALL SUGGEST(:kw, '".$index_table."')");
    $stmt->bindParam(":kw", $kw, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll();
    $res = [];
    foreach ($result as $value)
        if (count($res) < $limit)
            if ($value['distance'] <= $max_distance) {
                $res[] = $value['suggest'];
                $stmt = $pdo->prepare("CALL KEYWORDS(:sgst, '".$index_table."')");
                $stmt->bindParam(":sgst", $value['suggest'], PDO::PARAM_STR);
                $stmt->execute();
                $result_kw = $stmt->fetchAll();

                $stmt = $pdo->prepare("CALL KEYWORDS('*".$result_kw[0]['normalized']."*', '".$index_table."')");
                $stmt->execute();
                $result_kw2 = $stmt->fetchAll();
                foreach ($result_kw2 as $kw2) 
                    if (count($res) < $limit) $res[] = $kw2['normalized'];
            }
    
    return $res;
}

function getSequences($arr) {
    $result = array();
    $total = count($arr);
    while(true) {
        $row = array();
        foreach ($arr as $key => $value) $row[] = current($value);

        $result[] = implode(' ', $row);
        for ($i = $total - 1; $i >= 0; $i--)
            if (next($arr[$i])) 
                break;
            elseif ($i == 0) 
                break 2;
            else 
                reset($arr[$i]);
    }
    return $result;
}

$_limit = 100;
$_distance = 10;

// Ищем точное совпадение
getMatch($kw);

// Делим на слова
$words = preg_split('/\s+/', $kw);
if (count($words) > 1) {
    $sequences = [];
    $word_table = [];
    foreach ($words as $word)
        $word_table[] = getSuggests($word, $_distance, $_limit); 

    $sequences = getSequences($word_table);
    // print_r($word_table);
    // print_r([]);
    // print_r($sequences);

    foreach ($sequences as $seq)
        getMatch($seq);
}
else // Ищем по прдложенным, если слово одно
    foreach (getSuggests($words[0], $_distance, $_limit) as $sgst)
        getMatch($sgst);

$response = [ 'keywords' => [], 'match' => [], 'suggest' => [] ];


$id_list = [];
foreach ($res['match'] as $value) {
    if (!in_array($value['id'], $id_list)) {
        $response['match'][] = $value;
        array_push($id_list, $value['id']);
    }
}


echo json_encode($response, true);