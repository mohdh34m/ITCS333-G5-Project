<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));

function validateInput($data) {
    $errors = [];
    if (empty($data['title']) || strlen($data['title']) > 255) {
        $errors[] = 'Title is required and must be under 255 characters';
    }
    if (empty($data['date'])) {
        $errors[] = 'Date is required';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date']) || 
              strtotime($data['date']) < strtotime('today')) {
        $errors[] = 'Date must be valid and not in the past';
    }
    if (!empty($data['location']) && strlen($data['location']) > 100) {
        $errors[] = 'Location must be under 100 characters';
    }
    if (empty($data['category']) || !in_array($data['category'], ['academic', 'social', 'sports'])) {
        $errors[] = 'Valid category is required';
    }
    if (!empty($data['description']) && strlen($data['description']) > 500) {
        $errors[] = 'Description must be under 500 characters';
    }
    return $errors;
}

switch ($method) {
    case 'GET':
        if (isset($path[1]) && is_numeric($path[1])) {
            $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ?');
            $stmt->execute([$path[1]]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($event) {
                echo json_encode($event);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Event not found']);
            }
        } else {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = 6;
            $offset = ($page - 1) * $perPage;
            $search = isset($_GET['search']) ? '%' . trim($_GET['search']) . '%' : '%';
            $category = $_GET['category'] ?? '';
            $dateFilter = $_GET['date'] ?? '';

            $where = 'WHERE 1=1';
            $params = [];
            if ($search !== '%') {
                $where .= ' AND title LIKE ?';
                $params[] = $search;
            }
            if ($category) {
                $where .= ' AND category = ?';
                $params[] = $category;
            }
            if ($dateFilter) {
                if ($dateFilter === 'today') {
                    $where .= ' AND date = CURDATE()';
                } elseif ($dateFilter === 'week') {
                    $where .= ' AND date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)';
                } elseif ($dateFilter === 'month') {
                    $where .= ' AND date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 MONTH)';
                }
            }

            $sort = $_GET['sort'] ?? 'date';
            $sort = in_array($sort, ['date', 'title', 'location']) ? $sort : 'date';
            $order = "ORDER BY $sort ASC";

            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM events $where");
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT * FROM events $where $order LIMIT ? OFFSET ?");
            $stmt->execute([...$params, $perPage, $offset]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'events' => $events,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage
            ]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $errors = validateInput($data);
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            exit;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO events (title, date, location, category, description) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['title'],
            $data['date'],
            $data['location'] ?? null,
            $data['category'],
            $data['description'] ?? null
        ]);
        echo json_encode(['id' => $pdo->lastInsertId(), 'message' => 'Event created']);
        break;

    case 'PUT':
        if (!isset($path[1]) || !is_numeric($path[1])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid event ID']);
            exit;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $errors = validateInput($data);
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            exit;
        }

        $stmt = $pdo->prepare(
            'UPDATE events SET title = ?, date = ?, location = ?, category = ?, description = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['title'],
            $data['date'],
            $data['location'] ?? null,
            $data['category'],
            $data['description'] ?? null,
            $path[1]
        ]);
        if ($stmt->rowCount()) {
            echo json_encode(['message' => 'Event updated']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Event not found']);
        }
        break;

    case 'DELETE':
        if (!isset($path[1]) || !is_numeric($path[1])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid event ID']);
            exit;
        }
        $stmt = $pdo->prepare('DELETE FROM events WHERE id = ?');
        $stmt->execute([$path[1]]);
        if ($stmt->rowCount()) {
            echo json_encode(['message' => 'Event deleted']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Event not found']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?>