<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");
$host = "127.0.0.1";
$user = getenv("db_user") ?: "root";
$pass = getenv("db_pass") ?: "";
$db = getenv("db_name") ?: "events_calendar";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed: " . $e->getMessage()]);
    exit;
}
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
function validateEvent($data) {
    $errors = [];
    if (empty($data['title'])) $errors[] = "Event title is required.";
    if (empty($data['date'])) $errors[] = "Date is required.";
    elseif (strtotime($data['date']) < strtotime(date('Y-m-d'))) $errors[] = "Date cannot be in the past.";
    if (!empty($data['location']) && strlen($data['location']) > 100) $errors[] = "Location must be under 100 characters.";
    if (!empty($data['description']) && strlen($data['description']) > 500) $errors[] = "Description must be under 500 characters.";
    if (empty($data['category']) || !in_array($data['category'], ['academic', 'social', 'sports'])) {
        $errors[] = "Invalid category.";
    }
    return $errors;
}
$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$endpoint = $request_uri[1] ?? '';
if ($endpoint === 'api') {
    $api_endpoint = $request_uri[2] ?? '';
    switch ($api_endpoint) {
        case 'events':
            if ($request_method === 'GET') {
                $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                $per_page = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 6;
                $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
                $category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
                $date_filter = isset($_GET['date_filter']) ? sanitizeInput($_GET['date_filter']) : '';
                $sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'date';
                $query = "SELECT * FROM events WHERE 1=1";
                $params = [];
                if ($search) {
                    $query .= " AND (title LIKE :search OR description LIKE :search)";
                    $params[':search'] = "%$search%";
                }
                if ($category && in_array($category, ['academic', 'social', 'sports'])) {
                    $query .= " AND category = :category";
                    $params[':category'] = $category;
                }
                if ($date_filter === 'today') {
                    $query .= " AND date = :today";
                    $params[':today'] = date('Y-m-d');
                } elseif ($date_filter === 'week') {
                    $query .= " AND date BETWEEN :today AND :week";
                    $params[':today'] = date('Y-m-d');
                    $params[':week'] = date('Y-m-d', strtotime('+7 days'));
                } elseif ($date_filter === 'month') {
                    $query .= " AND date BETWEEN :today AND :month";
                    $params[':today'] = date('Y-m-d');
                    $params[':month'] = date('Y-m-d', strtotime('+1 month'));
                }
                $sort_fields = ['date' => 'date', 'title' => 'title', 'location' => 'location'];
                $sort = isset($sort_fields[$sort]) ? $sort_fields[$sort] : 'date';
                $query .= " ORDER BY $sort ASC";
                $offset = ($page - 1) * $per_page;
                $query .= " LIMIT :offset, :per_page";
                $params[':offset'] = $offset;
                $params[':per_page'] = $per_page;
                try {
                    $stmt = $pdo->prepare($query);
                    foreach ($params as $key => $value) {
                        if ($key === ':offset' || $key === ':per_page') {
                            $stmt->bindValue($key, $value, PDO::PARAM_INT);
                        } else {
                            $stmt->bindValue($key, $value);
                        }
                    }
                    $stmt->execute();
                    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $count_query = "SELECT COUNT(*) FROM events WHERE 1=1";
                    if ($search) $count_query .= " AND (title LIKE :search OR description LIKE :search)";
                    if ($category) $count_query .= " AND category = :category";
                    if ($date_filter === 'today') $count_query .= " AND date = :today";
                    elseif ($date_filter === 'week') $count_query .= " AND date BETWEEN :today AND :week";
                    elseif ($date_filter === 'month') $count_query .= " AND date BETWEEN :today AND :month";
                    $count_stmt = $pdo->prepare($count_query);
                    if ($search) $count_stmt->bindValue(':search', "%$search%");
                    if ($category) $count_stmt->bindValue(':category', $category);
                    if ($date_filter === 'today') $count_stmt->bindValue(':today', date('Y-m-d'));
                    elseif ($date_filter === 'week') {
                        $count_stmt->bindValue(':today', date('Y-m-d'));
                        $count_stmt->bindValue(':week', date('Y-m-d', strtotime('+7 days')));
                    } elseif ($date_filter === 'month') {
                        $count_stmt->bindValue(':today', date('Y-m-d'));
                        $count_stmt->bindValue(':month', date('Y-m-d', strtotime('+1 month')));
                    }
                    $count_stmt->execute();
                    $total = $count_stmt->fetchColumn();
                    http_response_code(200);
                    echo json_encode([
                        'events' => $events,
                        'total' => $total,
                        'page' => $page,
                        'per_page' => $per_page
                    ]);
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode(["error" => "Failed to fetch events: " . $e->getMessage()]);
                }
            } elseif ($request_method === 'POST') {
                $data = json_decode(file_get_contents("php://input"), true);
                $errors = validateEvent($data);
                if (!empty($errors)) {
                    http_response_code(400);
                    echo json_encode(["errors" => $errors]);
                    exit;
                }
                try {
                    $stmt = $pdo->prepare(
                        "INSERT INTO events (title, date, location, category, description) 
                         VALUES (:title, :date, :location, :category, :description)"
                    );
                    $stmt->execute([
                        ':title' => sanitizeInput($data['title']),
                        ':date' => $data['date'],
                        ':location' => sanitizeInput($data['location']),
                        ':category' => $data['category'],
                        ':description' => sanitizeInput($data['description'])
                    ]);
                    http_response_code(201);
                    echo json_encode(["message" => "Event created successfully", "id" => $pdo->lastInsertId()]);
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode(["error" => "Failed to create event: " . $e->getMessage()]);
                }
            }
            break;
        case 'events':
            $id = $request_uri[3] ?? null;
            if (!$id || !is_numeric($id)) {
                http_response_code(400);
                echo json_encode(["error" => "Invalid event ID"]);
                break;
            }
            if ($request_method === 'GET') {
                try {
                    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = :id");
                    $stmt->execute([':id' => $id]);
                    $event = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!$event) {
                        http_response_code(404);
                        echo json_encode(["error" => "Event not found"]);
                        break;
                    }
                    $comment_stmt = $pdo->prepare("SELECT comment_text FROM comments WHERE event_id = :id ORDER BY created_at DESC");
                    $comment_stmt->execute([':id' => $id]);
                    $event['comments'] = $comment_stmt->fetchAll(PDO::FETCH_COLUMN);
                    http_response_code(200);
                    echo json_encode($event);
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode(["error" => "Failed to fetch event: " . $e->getMessage()]);
                }
            } elseif ($request_method === 'PUT') {
                $data = json_decode(file_get_contents("php://input"), true);
                $errors = validateEvent($data);
                if (!empty($errors)) {
                    http_response_code(400);
                    echo json_encode(["errors" => $errors]);
                    break;
                }
                try {
                    $stmt = $pdo->prepare(
                        "UPDATE events SET title = :title, date = :date, location = :location, 
                         category = :category, description = :description WHERE id = :id"
                    );
                    $stmt->execute([
                        ':title' => sanitizeInput($data['title']),
                        ':date' => $data['date'],
                        ':location' => sanitizeInput($data['location']),
                        ':category' => $data['category'],
                        ':description' => sanitizeInput($data['description']),
                        ':id' => $id
                    ]);
                    if ($stmt->rowCount() === 0) {
                        http_response_code(404);
                        echo json_encode(["error" => "Event not found"]);
                        break;
                    }
                    http_response_code(200);
                    echo json_encode(["message" => "Event updated successfully"]);
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode(["error" => "Failed to update event: " . $e->getMessage()]);
                }
            } elseif ($request_method === 'DELETE') {
                try {
                    $stmt = $pdo->prepare("DELETE FROM events WHERE id = :id");
                    $stmt->execute([':id' => $id]);
                    if ($stmt->rowCount() === 0) {
                        http_response_code(404);
                        echo json_encode(["error" => "Event not found"]);
                        break;
                    }
                    http_response_code(200);
                    echo json_encode(["message" => "Event deleted successfully"]);
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode(["error" => "Failed to delete event: " . $e->getMessage()]);
                }
            }
            break;
        case 'comments':
            if ($request_method === 'POST') {
                $data = json_decode(file_get_contents("php://input"), true);
                if (empty($data['event_id']) || !is_numeric($data['event_id']) || empty($data['comment_text'])) {
                    http_response_code(400);
                    echo json_encode(["error" => "Event ID and comment text are required"]);
                    break;
                }
                try {
                    $stmt = $pdo->prepare("SELECT id FROM events WHERE id = :id");
                    $stmt->execute([':id' => $data['event_id']]);
                    if (!$stmt->fetch()) {
                        http_response_code(404);
                        echo json_encode(["error" => "Event not found"]);
                        break;
                    }
                    $stmt = $pdo->prepare(
                        "INSERT INTO comments (event_id, comment_text) VALUES (:event_id, :comment_text)"
                    );
                    $stmt->execute([
                        ':event_id' => $data['event_id'],
                        ':comment_text' => sanitizeInput($data['comment_text'])
                    ]);
                    http_response_code(201);
                    echo json_encode(["message" => "Comment added successfully"]);
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode(["error" => "Failed to add comment: " . $e->getMessage()]);
                }
            }
            break;
        default:
            http_response_code(404);
            echo json_encode(["error" => "Endpoint not found"]);
            break;
    }
    exit;
}
header("Content-Type: text/html; charset=UTF-8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Calendar</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
    <style>
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 15px;
}
.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 25px 0;
}
.event-card {
    border: 1px solid white;
    padding: 15px;
    border-radius: 10px;
    transition: transform 0.2s, box-shadow 0.2s; 
}
.event-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); 
    cursor: pointer;
}
.search-filter-bar {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    margin: 15px 0;
}
.controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 15px 0;
}
.controls a[role="button"] {
    transition: background-color 0.2s; 
}
.controls a[role="button"]:hover:not(:disabled) {
    background-color: #e0e0e0; 
}
.form-grid {
    display: grid;
    gap: 15px;
    max-width: 600px;
    margin: 30px auto;
}
.error-messages {
    color: #d32f2f; 
    font-size: 0.9em;
    margin-bottom: 10px;
}
.detail-container {
    max-width: 800px;
    margin: 30px auto;
}
.comments-section {
    margin-top: 30px;
}
.comment-form {
    display: grid;
    gap: 10px;
    max-width: 600px;
    margin: 20px 0;
}
.comments-list p {
    background-color: #f9f9f9; 
    padding: 10px;
    border-radius: 5px;
    margin: 5px 0;
}
.events-grid p:not(.event-card) {
    text-align: center;
    font-style: italic;
    color: #a7a7a7; 
}
@media (max-width: 768px) {
    .search-filter-bar {
        flex-direction: column;
    }
    .controls {
        flex-direction: column;
        gap: 10px;
    }
}
    </style>
</head>
<body>
    <main class="container" id="events-listing">
        <header>
            <h1>Events Calendar</h1>
        </header>
    <section class="search-filter-bar">
        <input type="search" placeholder="Search events..." aria-label="Search events">
        <select aria-label="Filter by category">
            <option value="">All Categories</option>
            <option value="academic">Academic</option>
            <option value="social">Social</option>
            <option value="sports">Sports</option>
        </select>
        <select aria-label="Filter by date">
            <option value="">All Dates</option>
            <option value="today">Today</option>
            <option value="week">This Week</option>
            <option value="month">This Month</option>
        </select>
    </section>
    <section class="controls">
        <select aria-label="Sort events">
            <option value="">Sort by...</option>
            <option value="date">Date</option>
            <option value="title">Title</option>
            <option value="location">Location</option>
        </select>
        <a href="#create-event" class="button" role="button">Add New Event</a>
    </section>
    <section class="events-grid">
        <article class="event-card">
            <h2>Sample Event 1</h2>
            <p>Date: April 12, 2025</p>
            <p>Location: S18</p>
            <p>Category: Academic</p>
        </article>
    </section>
    <nav class="controls">
        <ul>
            <li><a href="#" role="button" aria-label="Previous page">Previous</a></li>
            <li><a href="#" role="button" aria-label="Next page">Next</a></li>
        </ul>
    </nav>
</main>
<main class="container" id="create-event" style="display:none;">
    <header>
        <h1>Create New Event</h1>
    </header>
    <form class="form-grid">
        <div class="error-messages"></div>
        <div>
            <label for="title">Event Title <span aria-label="required">*</span></label>
            <input type="text" id="title" required placeholder="Enter event title">
        </div>
        <div>
            <label for="date">Date <span aria-label="required">*</span></label>
            <input type="date" id="date" required>
        </div>
        <div>
            <label for="location">Location</label>
            <input type="text" id="location" placeholder="Enter location">
        </div>
        <div>
            <label for="category">Category</label>
            <select id="category">
                <option value="academic">Academic</option>
                <option value="social">Social</option>
                <option value="sports">Sports</option>
            </select>
        </div>
        <div>
            <label for="description">Description</label>
            <textarea id="description" placeholder="Event details..."></textarea>
        </div>
        <div>
            <button type="submit">Create Event</button>
            <a href="#events-listing" role="button" class="secondary">Cancel</a>
        </div>
    </form>
</main>
<main class="container" id="event-detail" style="display:none;">
    <section class="detail-container">
        <header>
            <h1>Sample Event 1</h1>
        </header>
        <article>
            <p><strong>Date:</strong> April 12, 2025</p>
            <p><strong>Location:</strong> S18</p>
            <p><strong>Category:</strong> Academic</p>
            <p><strong>Description:</strong> This is a sample event description...</p>
        </article>
        <div class="controls">
            <button>Edit Event</button>
            <button class="secondary">Delete Event</button>
            <a href="#events-listing" role="button">Back to Events</a>
        </div>
        <section class="comments-section">
            <h2>Comments</h2>
            <form class="comment-form">
                <div>
                    <label for="comment">Add a Comment</label>
                    <textarea id="comment" placeholder="Write your comment..." required></textarea>
                </div>
                <button type="submit">Submit Comment</button>
            </form>
            <article class="comments-list">
                <p>No comments yet.</p>
            </article>
        </section>
    </section>
</main>
<footer class="container">
    <p>© 2025 Event Calendar</p>
</footer>
<script>
    let events = [];
    let currentPage = 1;
    const eventsPerPage = 6;
    let editingEventId = null; 
    const eventsGrid = document.querySelector('.events-grid');
    const searchInput = document.querySelector('input[type="search"]');
    const categoryFilter = document.querySelector('select[aria-label="Filter by category"]');
    const dateFilter = document.querySelector('select[aria-label="Filter by date"]');
    const sortSelect = document.querySelector('select[aria-label="Sort events"]');
    const prevButton = document.querySelector('nav.controls a[role="button"]:first-child');
    const nextButton = document.querySelector('nav.controls a[role="button"]:last-child');
    const createForm = document.querySelector('.form-grid');
    const eventDetailSection = document.querySelector('#event-detail');
    const detailContainer = eventDetailSection.querySelector('.detail-container');
    const commentForm = document.querySelector('.comment-form');
    function sanitizeInput(input) {
        const div = document.createElement('div');
        div.textContent = input;
        return div.innerHTML;
    }
    function showLoading(container) {
        container.innerHTML = '<p>Loading...</p>';
    }
    function showError(container, message) {
        container.innerHTML = `<p style="color: red;">Error: ${message}</p>`;
    }
    async function fetchEvents() {
        try {
            showLoading(eventsGrid);
            const search = encodeURIComponent(searchInput.value);
            const category = encodeURIComponent(categoryFilter.value);
            const dateRange = encodeURIComponent(dateFilter.value);
            const sort = encodeURIComponent(sortSelect.value);
            const response = await fetch(
                `/index.php/api/events?page=${currentPage}&per_page=${eventsPerPage}&search=${search}&category=${category}&date_filter=${dateRange}&sort=${sort}`
            );
            if (!response.ok) throw new Error('Failed to fetch events');
            const data = await response.json();
            events = data.events.map(event => ({ ...event, comments: event.comments || [] }));
            renderEvents();
        } catch (error) {
            showError(eventsGrid, error.message);
        }
    }
    function renderEvents() {
        let filteredEvents = events.filter(event => {
            const searchTerm = sanitizeInput(searchInput.value).toLowerCase();
            const category = categoryFilter.value;
            const dateRange = dateFilter.value;
            const today = new Date().toISOString().split('T')[0];
            const matchesSearch = event.title.toLowerCase().includes(searchTerm) ||
                                 event.description.toLowerCase().includes(searchTerm);
            const matchesCategory = !category || event.category === category;
            let matchesDate = true;
            if (dateRange === 'today') {
                matchesDate = event.date === today;
            } else if (dateRange === 'week') {
                const eventDate = new Date(event.date);
                const weekFromNow = new Date();
                weekFromNow.setDate(weekFromNow.getDate() + 7);
                matchesDate = eventDate >= new Date(today) && eventDate <= weekFromNow;
            } else if (dateRange === 'month') {
                const eventDate = new Date(event.date);
                const monthFromNow = new Date();
                monthFromNow.setMonth(monthFromNow.getMonth() + 1);
                matchesDate = eventDate >= new Date(today) && eventDate <= monthFromNow;
            }
            return matchesSearch && matchesCategory && matchesDate;
        });
        const sortBy = sortSelect.value;
        filteredEvents.sort((a, b) => {
            if (sortBy === 'date') return new Date(a.date) - new Date(b.date);
            if (sortBy === 'title') return a.title.localeCompare(b.title);
            if (sortBy === 'location') return a.location.localeCompare(b.location);
            return 0;
        });
        const start = (currentPage - 1) * eventsPerPage;
        const end = start + eventsPerPage;
        const paginatedEvents = filteredEvents.slice(start, end);
        eventsGrid.innerHTML = paginatedEvents.length
            ? paginatedEvents.map(event => `
                <article class="event-card" data-id="${event.id}">
                  <h2>${sanitizeInput(event.title)}</h2>
                  <p>Date: <time datetime="${event.date}">${new Date(event.date).toLocaleDateString()}</time></p>
                  <p>Location: ${sanitizeInput(event.location)}</p>
                  <p>Category: ${sanitizeInput(event.category.charAt(0).toUpperCase() + event.category.slice(1))}</p>
                </article>
              `).join('')
            : '<p>No events found.</p>';
        prevButton.disabled = currentPage === 1;
        nextButton.disabled = end >= filteredEvents.length;
    }
    function showEventDetails(eventId) {
        fetch(`/index.php/api/events/${eventId}`)
        .then(response => {
            if (!response.ok) throw new Error('Event not found');
            return response.json();
        })
        .then(event => {
            detailContainer.innerHTML = `
                <header>
                    <h1>${sanitizeInput(event.title)}</h1>
                </header>
                <article>
                    <p><strong>Date:</strong> <time datetime="${event.date}">${new Date(event.date).toLocaleDateString()}</time></p>
                    <p><strong>Location:</strong> ${sanitizeInput(event.location)}</p>
                    <p><strong>Category:</strong> ${sanitizeInput(event.category.charAt(0).toUpperCase() + event.category.slice(1))}</p>
                    <p><strong>Description:</strong> ${sanitizeInput(event.description || 'No description available.')}</p>
                </article>
                <div class="controls">
                    <button data-action="edit" data-id="${event.id}">Edit Event</button>
                    <button class="secondary" data-action="delete" data-id="${event.id}">Delete Event</button>
                    <a href="#events-listing" role="button">Back to Events</a>
                </div>
                <section class="comments-section">
                    <h2>Comments</h2>
                    <form class="comment-form">
                        <div>
                            <label for="comment">Add a Comment</label>
                            <textarea id="comment" placeholder="Write your comment..." required></textarea>
                        </div>
                        <button type="submit">Submit Comment</button>
                    </form>
                    <article class="comments-list">
                        ${event.comments.length
                            ? event.comments.map(comment => `<p>${sanitizeInput(comment)}</p>`).join('')
                            : '<p>No comments yet.</p>'}
                    </article>
                </section>
            `;
            document.querySelectorAll('main.container').forEach(section => section.style.display = 'none');
            eventDetailSection.style.display = 'block';
        })
        .catch(error => showError(detailContainer, error.message));
    }
    function validateForm(formData) {
        const errors = [];
        if (!formData.title.trim()) errors.push('Event title is required.');
        if (!formData.date) errors.push('Date is required.');
        if (new Date(formData.date) < new Date().setHours(0, 0, 0, 0)) errors.push('Date cannot be in the past.');
        if (formData.location && formData.location.length > 100) errors.push('Location must be under 100 characters.');
        if (formData.description && formData.description.length > 500) errors.push('Description must be under 500 characters.');
        return errors;
    }
    function setupRealTimeValidation() {
        const fields = ['title', 'date', 'location', 'description'];
        fields.forEach(field => {
            const input = createForm.querySelector(`#${field}`);
            input.addEventListener('blur', () => {
                const formData = {
                    title: createForm.querySelector('#title').value,
                    date: createForm.querySelector('#date').value,
                    location: createForm.querySelector('#location').value,
                    category: createForm.querySelector('#category').value,
                    description: createForm.querySelector('#description').value
                };
                const errors = validateForm(formData);
                const errorContainer = createForm.querySelector('.error-messages');
                errorContainer.innerHTML = errors.length
                    ? errors.map(err => `<p>${err}</p>`).join('')
                    : '';
            });
        });
    }
    function populateFormForEdit(eventId) {
        fetch(`/index.php/api/events/${eventId}`)
        .then(response => response.json())
        .then(event => {
            if (event.error) throw new Error(event.error);
            editingEventId = eventId;
            createForm.querySelector('#title').value = event.title;
            createForm.querySelector('#date').value = event.date;
            createForm.querySelector('#location').value = event.location;
            createForm.querySelector('#category').value = event.category;
            createForm.querySelector('#description').value = event.description;
            createForm.querySelector('button[type="submit"]').textContent = 'Update Event';
            document.querySelector('#create-event header h1').textContent = 'Edit Event';
            document.querySelectorAll('main.container').forEach(section => section.style.display = 'none');
            document.querySelector('#create-event').style.display = 'block';
        })
        .catch(error => showError(createForm.querySelector('.error-messages'), error.message));
    }
    function handleFormSubmit(e) {
        e.preventDefault();
        const formData = {
            title: sanitizeInput(createForm.querySelector('#title').value),
            date: createForm.querySelector('#date').value,
            location: sanitizeInput(createForm.querySelector('#location').value),
            category: createForm.querySelector('#category').value,
            description: sanitizeInput(createForm.querySelector('#description').value)
        };
        const errors = validateForm(formData);
        const errorContainer = createForm.querySelector('.error-messages');
        errorContainer.innerHTML = errors.length
            ? errors.map(err => `<p>${err}</p>`).join('')
            : '';
        if (errors.length) return;
        const method = editingEventId ? 'PUT' : 'POST';
        const url = editingEventId ? `/index.php/api/events/${editingEventId}` : '/index.php/api/events';
        fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.errors) {
                errorContainer.innerHTML = data.errors.map(err => `<p>${err}</p>`).join('');
                return;
            }
            alert(editingEventId ? 'Event updated successfully!' : 'Event created successfully!');
            createForm.reset();
            editingEventId = null;
            createForm.querySelector('button[type="submit"]').textContent = 'Create Event';
            document.querySelector('#create-event header h1').textContent = 'Create New Event';
            errorContainer.innerHTML = '';
            window.location.hash = 'events-listing';
            fetchEvents();
        })
        .catch(error => {
            errorContainer.innerHTML = `<p>Error: ${error.message}</p>`;
        });
    }
    function handleCommentSubmit(e, eventId) {
        e.preventDefault();
        const commentText = sanitizeInput(commentForm.querySelector('#comment').value);
        if (!commentText.trim()) return;
        fetch('/index.php/api/comments', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ event_id: eventId, comment_text: commentText })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) throw new Error(data.error);
            commentForm.reset();
            showEventDetails(eventId);
        })
        .catch(error => showError(detailContainer, error.message));
    }
    document.addEventListener('DOMContentLoaded', () => {
        fetchEvents();
        setupRealTimeValidation();
        searchInput.addEventListener('input', () => { currentPage = 1; renderEvents(); });
        categoryFilter.addEventListener('change', () => { currentPage = 1; renderEvents(); });
        dateFilter.addEventListener('change', () => { currentPage = 1; renderEvents(); });
        sortSelect.addEventListener('change', () => renderEvents());
        prevButton.addEventListener('click', (e) => {
            e.preventDefault();
            if (currentPage > 1) {
                currentPage--;
                renderEvents();
            }
        });
        nextButton.addEventListener('click', (e) => {
            e.preventDefault();
            currentPage++;
            renderEvents();
        });
        eventsGrid.addEventListener('click', (e) => {
            const card = e.target.closest('.event-card');
            if (card) {
                const eventId = card.dataset.id;
                showEventDetails(eventId);
                window.location.hash = `event-detail/${eventId}`;
            }
        });
        createForm.addEventListener('submit', handleFormSubmit);
        eventDetailSection.addEventListener('click', (e) => {
            const button = e.target.closest('button[data-action]');
            if (button) {
                const eventId = button.dataset.id;
                if (button.dataset.action === 'edit') {
                    populateFormForEdit(eventId);
                    window.location.hash = 'create-event';
                } else if (button.dataset.action === 'delete') {
                    if (confirm('Are you sure you want to delete this event?')) {
                        fetch(`/index.php/api/events/${eventId}`, { method: 'DELETE' })
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) throw new Error(data.error);
                            window.location.hash = 'events-listing';
                            fetchEvents();
                        })
                        .catch(error => showError(detailContainer, error.message));
                    }
                }
            }
        });
        eventDetailSection.addEventListener('submit', (e) => {
            if (e.target.classList.contains('comment-form')) {
                const eventId = window.location.hash.split('/')[1];
                handleCommentSubmit(e, eventId);
            }
        });
        window.addEventListener('hashchange', () => {
            const hash = window.location.hash.slice(1);
            document.querySelectorAll('main.container').forEach(section => section.style.display = 'none');
            if (hash.startsWith('event-detail/')) {
                const eventId = hash.split('/')[1];
                showEventDetails(eventId);
            } else if (hash === 'create-event') {
                document.querySelector('#create-event').style.display = 'block';
            } else {
                document.querySelector('#events-listing').style.display = 'block';
            }
        });
        if (window.location.hash) {
            window.dispatchEvent(new Event('hashchange'));
        }
    });
</script>
</body>
</html>
