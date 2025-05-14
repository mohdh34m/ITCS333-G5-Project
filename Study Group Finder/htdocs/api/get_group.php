<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require 'db.php';

$groupId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($groupId === 0) {
    echo json_encode(["error" => "Invalid group ID."]);
    exit;
}

try {
    $sql = "
        SELECT 
            sg.id,
            sg.title,
            sg.subject,
            sg.location,
            sg.date,
            sg.time,
            sg.max_members AS members,
            sg.description,
            sg.course,
            COUNT(gm.id) AS joined_members
        FROM study_groups sg
        LEFT JOIN group_members gm ON gm.group_id = sg.id
        WHERE sg.id = :groupId
        GROUP BY sg.id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':groupId', $groupId, PDO::PARAM_INT);
    $stmt->execute();
    $group = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($group) {
        echo json_encode($group);
    } else {
        echo json_encode(["error" => "Group not found."]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>