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

// Verify task ownership through list → project → user
$stmt = $conn->prepare("SELECT p.user_id, f.path 
                        FROM tasks t
                        JOIN lists l ON t.list_id = l.id
                        JOIN projects p ON l.project_id = p.id
                        LEFT JOIN files f ON t.id = f.task_id
                        WHERE t.id = ?");
$stmt->bind_param("i", $task_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Task not found']);
    exit;
}

$data = $result->fetch_assoc();
$task_owner = $data['user_id'];
$file_path = $data['path'] ?? null;

if ($task_owner != $_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['error' => 'Not authorized to delete this task']);
    exit;
}

// Delete the task (files will be deleted by cascade)
$stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
$stmt->bind_param("i", $task_id);

if ($stmt->execute()) {
    // Delete the associated file if exists
    if ($file_path && file_exists($file_path)) {
        unlink($file_path);
    }
    
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete task']);
}