<?php

define('TOKEN', file_get_contents('token'));

// если пришли параметры на исполнение команды от хранилища
if ($_POST['token'] && $_POST['token'] === TOKEN && $_POST['id'] && $_POST['cmd']) {
    $log = '';
    $cmd = escapeshellcmd($_POST['cmd']);
    $result = exec($cmd, $output, $return);
    $log .= PHP_EOL . date('Y-m-d hh:ii:ss') . ': ' . $cmd;
    $log .= PHP_EOL . 'OUT:' . PHP_EOL . $output;
    $log .= PHP_EOL . 'ERR:' . PHP_EOL . $return . PHP_EOL;

    // TODO логирование

    // отправляем запрос скрипту хранилища о завершении задачи
    $query = [
        'token' => $token,
        'finished' => $id
    ];

    $url = 'http://url/to/storage_script.php';

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
    curl_exec($curl);
    curl_close($curl);

}
elseif (isCron()) {
    // отправляем запрос скрипту хранилища
    $query = [
        'token' => $token
    ];

    $url = 'http://url/to/storage_script.php';

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
    curl_exec($curl);
    curl_close($curl);

    $result = json_decode($JSONresult);
}


function isCron(): bool
{
    // здесь определять, запущен ли скрипт через крон, пока заглушка
    return true;
}