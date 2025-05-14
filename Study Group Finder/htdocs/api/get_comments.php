<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require 'db.php';

$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;

if (!$groupId) {
    echo json_encode(["error" => "Missing group_id"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT comment, created_at, name 
        FROM comments 
        WHERE group_id = :group_id
        ORDER BY created_at DESC
    ");
    $stmt->execute(['group_id' => $groupId]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($comments);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
