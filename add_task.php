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

$list_id = intval($_POST['list_id']);
$title = trim($conn->real_escape_string($_POST['title']));
$description = trim($conn->real_escape_string($_POST['description'] ?? ''));
$priority = in_array($_POST['priority'], ['low', 'medium', 'high']) ? $_POST['priority'] : 'medium';
$deadline = !empty($_POST['deadline']) ? $conn->real_escape_string($_POST['deadline']) : null;

if (empty($title)) {
    http_response_code(400);
    echo json_encode(['error' => 'Task title is required']);
    exit;
}

// Get max position for tasks in this list
$stmt = $conn->prepare("SELECT COALESCE(MAX(position), 0) + 1 AS new_position FROM tasks WHERE list_id = ?");
$stmt->bind_param("i", $list_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$position = $result['new_position'];

// Insert task
$stmt = $conn->prepare("INSERT INTO tasks (list_id, title, description, priority, deadline, position) 
                        VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("issssi", $list_id, $title, $description, $priority, $deadline, $position);

if ($stmt->execute()) {
    $task_id = $stmt->insert_id;
    
    // Handle file upload
    if (!empty($_FILES['attachment']['name'])) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $filename = basename($_FILES['attachment']['name']);
        $target_path = $upload_dir . uniqid() . '_' . $filename;
        
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_path)) {
            $stmt = $conn->prepare("INSERT INTO files (task_id, filename, path) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $task_id, $filename, $target_path);
            $stmt->execute();
        }
    }
    
    echo json_encode([
        'success' => true, 
        'task' => [
            'id' => $task_id,
            'title' => $title,
            'description' => $description,
            'priority' => $priority,
            'deadline' => $deadline,
            'position' => $position
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create task']);
}