<?php

header("Access-Control-Allow-Origin: *");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

const DEBUG_LOG = false;
$log = new stdClass();


// Если вместо 127.0.0.1 написать localhost, то под линуксом PDO может приконнектиться к MySQL
$pdo = new PDO('mysql:host=127.0.0.1;port=9306');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$res = [];
$res['match'] = [];
$kw = $_GET['kw'];
$index_table = $_GET['index'];

if (file_exists("settings/$index_table.json")) {
    $settings = json_decode(file_get_contents("settings/$index_table.json"), true);
    $_limit = isset($settings['limit']) ? $settings['limit'] : 1000;
    $_suggests = isset($settings['suggests']) ? $settings['suggests'] : 100;
    $_distance = isset($settings['distance']) ? $settings['distance'] : 10;
    $_words_limit = isset($settings['words_limit']) ? $settings['words_limit'] : 5;
} else {
    $_limit = 1000;
    $_suggests = 100;
    $_distance = 10;
    $_words_limit = 5;
}


if (!preg_match("/^([a-zA-Z0-9]+)$/", $index_table))
    die('Invalid indexname');


function _array_push(&$array, &$items) { foreach ($items as &$value) $array[] = $value; }

function matchQuery($kw, &$log) {
    global $pdo, $res, $lim, $index_table;
    $stmt = $pdo->prepare("SELECT * FROM ".$index_table." WHERE MATCH(:kw) LIMIT 1000");
    $stmt->bindParam(":kw", $kw, PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll();
    if (DEBUG_LOG) array_push($log, [
        "keyword" => "[$kw]",
        "result" => count($results)
    ]);
    _array_push($res['match'], $results);
}


function getMatch($kw, &$log) {
    matchQuery('=^"'.$kw.'"', $log);
    matchQuery('="'.$kw.'"', $log);
    matchQuery('=^'.$kw.'', $log);
    matchQuery('^'.$kw.'', $log);
    matchQuery('=^'.$kw.'*', $log);
    matchQuery('^'.$kw.'*', $log);
    matchQuery('='.$kw.'', $log);
    matchQuery(''.$kw.'', $log);
    matchQuery('=*'.$kw.'*', $log);
    matchQuery('*'.$kw.'*', $log);

    // Делим на слова
    $words = preg_split('/\s+/', $kw);
    if (count($words) > 1) {

		// Ставим вначале =
        $query = '';
        foreach ($words as $word)
            if (mb_strlen($word) > 2) $query .= '*='.$word.'*'.' ';
            else if (mb_strlen($word) == 2) $query .= '='.$word.'*'.' ';

        $query = substr($query, 0, -1);
        matchQuery($query, $log);

		// Тоже самое, но без =
        $query = '';
        foreach ($words as $word)
            if (mb_strlen($word) > 2) $query .= '*'.$word.'*'.' ';
            else if (mb_strlen($word) == 2) $query .= $word.'*'.' ';

        $query = substr($query, 0, -1);
        matchQuery($query, $log);
    }
}


function callKeywords(&$res, $sgst, $limit) {
    global $pdo, $index_table;
    $stmt = $pdo->prepare("CALL KEYWORDS(:sgst, '".$index_table."')");
    $stmt->bindParam(":sgst", $sgst, PDO::PARAM_STR);
    $stmt->execute();
    $result_kw = $stmt->fetchAll();
    foreach ($result_kw as $kw) 
        if (count($res) < $limit && !in_array($kw['normalized'], $res)) 
            $res[] = $kw['normalized'];
}


function getSuggests($kw, $max_distance, $limit) {
    global $pdo, $index_table;
    $stmt = $pdo->prepare("CALL SUGGEST(:kw, '".$index_table."')");
    $stmt->bindParam(":kw", $kw, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll();
    $res = [];
    $res[] = $kw;
    callKeywords($res, $kw, $limit);

    foreach ($result as $value)
        if (count($res) < $limit)
            if ($value['distance'] <= $max_distance)
                callKeywords($res, $value['suggest'], $limit);
    
    return $res;
}

function getSequences($arr, $words_limit) {
    $result = array();
    $total = count($arr);
    $total = $total > $words_limit ? $words_limit : $total;
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

if (DEBUG_LOG) {
    $log->name = $kw;
    $log->absolute = [];
    $log->suggests = [];
}

// Ищем точное совпадение
getMatch($kw, $log->absolute);

// Делим на слова
$words = preg_split('/\s+/', $kw);
if (count($words) > 1) {
    $sequences = [];
    $word_table = [];
    foreach ($words as $word)
        $word_table[] = getSuggests($word, $_distance, $_suggests); 

    
    $sequences = getSequences($word_table, $_words_limit);

    if (DEBUG_LOG) {
        $log->word_table = $word_table;
        $log->sequences = $sequences;
    }

    foreach ($sequences as $seq)
        getMatch($seq, $log->suggests);
}
else // Ищем по прдложенным, если слово одно
    foreach (getSuggests($words[0], $_distance, $_suggests) as $sgst)
        getMatch($sgst, $log->suggests);

$response = [ 'keywords' => [], 'match' => [], 'suggest' => [] ];


$id_list = [];
foreach ($res['match'] as $value) {
    if (!in_array($value['id'], $id_list)) {
        $response['match'][] = $value;
        array_push($id_list, $value['id']);
    }
}

$response['match'] = array_slice($response['match'], 0, $_limit);

if (DEBUG_LOG) {
    file_put_contents("logs/mcsearch.log", print_r($log, true), FILE_APPEND);
}

echo json_encode($response, true);