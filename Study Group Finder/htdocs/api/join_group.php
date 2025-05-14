<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$name = $data['name'] ?? '';
$studentId = $data['student_id'] ?? '';
$groupId = $data['group_id'] ?? 0;

if (!$name || !$studentId || !$groupId) {
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO group_members (group_id, name, student_id, joined_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$groupId, $name, $studentId]);

    echo json_encode(["message" => "You joined the group successfully!"]);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>