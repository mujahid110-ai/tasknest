<?php
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF token validation failed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$title = trim($conn->real_escape_string($_POST['title']));

if (empty($title)) {
    http_response_code(400);
    echo json_encode(['error' => 'Project title is required']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO projects (user_id, title) VALUES (?, ?)");
$stmt->bind_param("is", $_SESSION['user_id'], $title);

if ($stmt->execute()) {
    $project_id = $stmt->insert_id;
    
    // Create default lists for the new project
    $default_lists = [
        ['To Do', 1],
        ['In Progress', 2],
        ['Done', 3]
    ];
    
    foreach ($default_lists as $list) {
        $stmt = $conn->prepare("INSERT INTO lists (project_id, title, position) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $project_id, $list[0], $list[1]);
        $stmt->execute();
    }
    
    echo json_encode(['success' => true, 'project_id' => $project_id]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create project']);
}