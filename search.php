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
            if ($value['distance'] <= $max_distance)
                $res[] = $value['suggest'];
    
    // Применим CALL KEYWORDS
    foreach ($res as &$sgst) {
        $stmt = $pdo->prepare("CALL KEYWORDS(:sgst, '".$index_table."')");
        $stmt->bindParam(":sgst", $sgst, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll();
        $sgst = $result[0]['normalized'];
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

// Ищем точное совпадение
getMatch($kw);

// Делим на слова
$words = preg_split('/\s+/', $kw);
if (count($words) > 1) {
    $sequences = [];
    $word_table = [];
    foreach ($words as $word)
        $word_table[] = getSuggests($word, 10, 3); 

    foreach (getSequences($word_table) as $seq)
        getMatch($seq);
}
else // Ищем по прдложенным, если слово одно
    foreach (getSuggests($words[0], 10, 3) as $sgst)
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