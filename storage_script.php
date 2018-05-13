<?php

if (empty($_POST['token']))
    die;

define('TOKEN', file_get_contents('token'));
$request = $_POST['token'];
// подключаемся к базе
$mysqlAccess = file('db.conf');
$mysqli = new mysqli($mysqlAccess[0], $mysqlAccess[1], $mysqlAccess[2], $mysqlAccess[3]);

if (mysqli_connect_errno()) {
    printf("Не удалось подключиться: %s\n", mysqli_connect_error());
    exit();
}

// если от воркера пришло сообщение о выполнении задания, сменим задаче статус
if ($request === TOKEN && $_POST['finished']) {
    $id = (int)$_POST['finished'];
    $mysqli->query("UPDATE tasks SET state='finished' WHERE id = $id;");
}
// если просто совпали токены, значит воркер хочет проверить, нет ли задач
elseif ($request === TOKEN) {
    $stmt =  $mysqli->stmt_init();
    $now = date('Y-m-d hh:ii') . ':00';
    // смотрим, есть ли в базе еще не выполненная задача на текущую минуту
    if ($stmt->prepare("SELECT TOP 1 id, task FROM tasks WHERE time=? AND state='new'")) {
        $stmt->bind_param("s", $now);
        // блокируем базу перед поиском, чтобы другой воркер не смог ту же задачу
        $mysqli->query("LOCK TABLES tasks WRITE;");
        $stmt->execute();
        $stmt->bind_result($id, $cmd);
        $stmt->fetch();
        $stmt->close();

        if (!empty($id) && !empty($cmd)) {
            $mysqli->query("UPDATE tasks SET state='progress' WHERE id = $id;");
            $mysqli->query("UNLOCK TABLES;");
            // шлем воркеру запрос на выполнение задачи
            execCommandOnWorker($id, $cmd);
        }
    }

    /* закрываем соединение */
    $mysqli->close();
}

function execCommandOnWorker($id, $cmd) : void
{
    $query = [
        'token' => $token,
        'id' => $id,
        'cmd' => $cmd
    ];

    $url = 'http://url/to/worker_script.php';

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
    curl_exec($curl);
    curl_close($curl);
}