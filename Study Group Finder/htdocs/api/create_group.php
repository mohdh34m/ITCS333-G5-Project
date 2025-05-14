<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");

require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["error" => "Invalid data"]);
    exit;
}

try {
    $sql = "INSERT INTO study_groups 
        (title, subject, course, description, location, date, time, max_members) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['title'],
        $data['subject'],
        $data['course'],
        $data['description'],
        $data['location'],
        $data['date'],
        $data['time'],
        $data['members']
    ]);

    echo json_encode(["message" => "Group created successfully."]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
