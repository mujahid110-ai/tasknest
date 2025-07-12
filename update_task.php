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

$task_id = intval($_POST['task_id']);
$title = trim($conn->real_escape_string($_POST['title']));
$description = trim($conn->real_escape_string($_POST['description'] ?? ''));
$priority = in_array($_POST['priority'], ['low', 'medium', 'high']) ? $_POST['priority'] : 'medium';
$deadline = !empty($_POST['deadline']) ? $conn->real_escape_string($_POST['deadline']) : null;

if (empty($title)) {
    http_response_code(400);
    echo json_encode(['error' => 'Task title is required']);
    exit;
}

// Verify task ownership through list → project → user
$stmt = $conn->prepare("SELECT p.user_id 
                        FROM tasks t
                        JOIN lists l ON t.list_id = l.id
                        JOIN projects p ON l.project_id = p.id
                        WHERE t.id = ?");
$stmt->bind_param("i", $task_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Task not found']);
    exit;
}

$task_owner = $result->fetch_assoc()['user_id'];

if ($task_owner != $_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['error' => 'Not authorized to update this task']);
    exit;
}

// Update task
$stmt = $conn->prepare("UPDATE tasks 
                        SET title = ?, description = ?, priority = ?, deadline = ?
                        WHERE id = ?");
$stmt->bind_param("ssssi", $title, $description, $priority, $deadline, $task_id);

if ($stmt->execute()) {
    // Handle file upload if needed
    if (!empty($_FILES['attachment']['name'])) {
        // Delete existing file if any
        $conn->query("DELETE FROM files WHERE task_id = $task_id");
        
        $upload_dir = 'uploads/';
        $filename = basename($_FILES['attachment']['name']);
        $target_path = $upload_dir . uniqid() . '_' . $filename;
        
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_path)) {
            $stmt = $conn->prepare("INSERT INTO files (task_id, filename, path) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $task_id, $filename, $target_path);
            $stmt->execute();
        }
    }
    
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update task']);
}