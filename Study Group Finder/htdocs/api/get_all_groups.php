<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require 'db.php';

$page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$limit = isset($_GET['limit']) ? max((int)$_GET['limit'], 1) : 10;
$offset = ($page - 1) * $limit;

try {
    $noPagination = isset($_GET['paginate']) &&             $_GET['paginate'] === 'false';

    if ($noPagination) {
        $stmt = $pdo->prepare(
            "SELECT sg.*, COUNT(gm.id) AS joined_members 
             FROM study_groups sg 
             LEFT JOIN group_members gm ON sg.id = gm.group_id 
             GROUP BY sg.id"
        );
    } else {
        $stmt = $pdo->prepare(
            "SELECT sg.*, COUNT(gm.id) AS joined_members 
             FROM study_groups sg 
             LEFT JOIN group_members gm ON sg.id = gm.group_id 
             GROUP BY sg.id 
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    }
    $stmt->execute();
    $groups = $stmt->fetchAll();

    // Get total number of groups
    $totalStmt = $pdo->query("SELECT COUNT(*) FROM study_groups");
    $total = $totalStmt->fetchColumn();

    echo json_encode([
        "data" => $groups,
        "pagination" => [
            "current_page" => $page,
            "limit" => $limit,
            "total" => (int)$total,
            "total_pages" => ceil($total / $limit)
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>