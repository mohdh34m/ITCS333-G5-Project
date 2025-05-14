<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");

require 'db.php';

try {
  $sql = "SELECT id, title, subject, location, date, time, max_members, description, course FROM study_groups";
  $stmt = $pdo->prepare($sql);
  $stmt->execute();

  $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode($groups);

} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(["error" => $e->getMessage()]);
}
?>
