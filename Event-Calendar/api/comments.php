<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));

if (!isset($path[1]) || !is_numeric($path[1])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid event ID']);
    exit;
}

$eventId = $path[1];

switch ($method) {
    case 'GET':
        $stmt = $pdo->prepare('SELECT * FROM comments WHERE event_id = ? ORDER BY created_at DESC');
        $stmt->execute([$eventId]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($comments);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['comment_text']) || strlen($data['comment_text']) > 500) {
            http_response_code(400);
            echo json_encode(['error' => 'Comment is required and must be under 500 characters']);
            exit;
        }

        $stmt = $pdo->prepare('INSERT INTO comments (event_id, comment_text) VALUES (?, ?)');
        $stmt->execute([$eventId, $data['comment_text']]);
        echo json_encode(['id' => $pdo->lastInsertId(), 'message' => 'Comment added']);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?>