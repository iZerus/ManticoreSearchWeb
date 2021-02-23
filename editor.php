<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = parse_ini_file('config.ini');

if (isset($_POST['index'], $_POST['token'], $_POST['data'])) {
    $file = $_POST['index'];
    $token = $_POST['token'];
    if (isset($config[$file]) && $token == $config[$file] && file_exists(__DIR__.'/wordforms/'.$file.'.wfs'))
    {
        file_put_contents(__DIR__.'/wordforms/'.$file.'.wfs', $_POST['data']);
        $data = file_get_contents(__DIR__.'/wordforms/'.$file.'.wfs');
    }
    else
        die('Invalid index or token');
}
else if (isset($_GET['index'], $_GET['token'])) {
    $file = $_GET['index'];
    $token = $_GET['token'];
    if (isset($config[$file]) && $token == $config[$file] && file_exists(__DIR__.'/wordforms/'.$file.'.wfs'))
    {
        $data = file_get_contents(__DIR__.'/wordforms/'.$file.'.wfs');
    }
    else
        die('Invalid index or token');
}
else
    die('Error: index or token is undefined');

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <title>Словарь - <?php echo $file; ?></title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <style>
        textarea {
            width: 100%;
            height: 80vh;
            font-size: 20px;
        }
    </style>
</head>
<body>
    <button form="editor">Сохранить</button>
    <button form="indexer">Обновить индекс</button>
    <hr>
    <form id="indexer" target="_blank" action="indexer.php">
        <input type="hidden" name="index" value="<?php echo $file; ?>">
        <input type="hidden" name="token" value="<?php echo $token; ?>">
    </form>
    <form method="POST" id="editor">
        <section>
            <input type="hidden" name="index" value="<?php echo $file; ?>">
            <input type="hidden" name="token" value="<?php echo $token; ?>">
        </section>
        <section>
            <textarea name="data"><?php echo $data; ?></textarea>
        </section>
    </form>
</body>
</html>