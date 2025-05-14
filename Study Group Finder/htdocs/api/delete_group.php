<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: DELETE");

require 'db.php';

if (!isset($_GET['id'])) {
  echo json_encode(["error" => "No group ID provided."]);
  exit;
}

$group_id = $_GET['id'];

try {
  $stmt = $pdo->prepare("DELETE FROM study_groups WHERE id = ?");
  $stmt->execute([$group_id]);
  echo json_encode(["message" => "Study group deleted successfully."]);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(["error" => "Error deleting group: " . $e->getMessage()]);
}
?>