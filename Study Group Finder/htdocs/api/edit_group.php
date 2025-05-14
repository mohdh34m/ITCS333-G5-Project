<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit();
}

require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
  echo json_encode(["error" => "Missing group ID"]);
  exit;
}

try {
  $sql = "UPDATE study_groups SET 
            title = ?, subject = ?, location = ?, date = ?, time = ?, 
            max_members = ?, description = ?, course = ?
          WHERE id = ?";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    $data['title'],
    $data['subject'],
    $data['location'],
    $data['date'],
    $data['time'],
    $data['members'],
    $data['description'],
    $data['course'],
    $data['id']
  ]);

  echo json_encode(["message" => "Study group updated."]);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(["error" => $e->getMessage()]);
}
?>
