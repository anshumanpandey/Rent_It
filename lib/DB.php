<?php

$pdo = NULL;

function getDb() {
    global $pdo;
    if ($pdo) {
        return $pdo;
    }
    $host = 'localhost';
    $db   = 'rightcar_mainwebsite';
    $user = 'rightcar_zezgo';
    $pass = 'zezgo';

    $dsn = "mysql:host=$host;dbname=$db";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (PDOException $e) {
        throw new PDOException($e->getMessage(), (int)$e->getCode());
    }
}
function getDbForGrcgds($dbName = "grcgds_gateway_db") {
    $host = 'localhost';
    $db   = $dbName;
    $user = 'grcgds';
    $pass = 'ADF)4W?t1!hg';

    $dsn = "mysql:host=$host;dbname=$db";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (PDOException $e) {
        throw new PDOException($e->getMessage(), (int)$e->getCode());
    }
}

function getTableData($tablename){
    $pdo = getDb();
    $stmt = $pdo->prepare('DESCRIBE '. $tablename);
    $stmt->execute();

    foreach ($stmt->fetchAll() as $row) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
}

function getTables(){
    $pdo = getDb();
    $stmt = $pdo->prepare('show tables;');
    $stmt->execute();

    foreach ($stmt->fetchAll() as $row) {
        var_dump($row);
    }
}

function deleteUserByEmail($email){
    $pdo = getDb();
    $stmt = $pdo->prepare('DELETE FROM users WHERE username=:email');
    $stmt->execute(["email" => $email]);

    $stmt = $pdo->prepare('SELECT * from users WHERE username=:email limit 1');
    $stmt->execute(['email' => $email]);
    if ($stmt->rowCount() == 0) {
        echo "user deleted!";
    }
}