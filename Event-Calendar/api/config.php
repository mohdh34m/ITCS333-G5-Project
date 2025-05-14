<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $host = '127.0.0.1';
    $db = getenv('db_name') ?: 'mydb';
    $user = getenv('db_user')?: 'user2301';
    $pass = getenv('db_pass')?:'202101055';
    
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}
?>