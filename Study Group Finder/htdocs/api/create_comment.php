<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$name = $data['user_id'] ?? 'Anonymous';
$group_id = $data['group_id'];
$comment = $data['comment'] ?? '';

if (!$group_id || !$comment) {
    echo json_encode(["error" => "Missing data"]);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO comments (name, group_id, comment, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$name, $group_id, $comment]);
    echo json_encode(["message" => "Comment posted successfully"]);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>