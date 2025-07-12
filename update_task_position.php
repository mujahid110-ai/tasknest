<?php
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!verify_csrf_token($data['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF token validation failed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$task_id = intval($data['task_id']);
$list_id = intval($data['list_id']);
$position = intval($data['position']);

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
    echo json_encode(['error' => 'Not authorized to move this task']);
    exit;
}

// Update task position and list
$stmt = $conn->prepare("UPDATE tasks SET list_id = ?, position = ? WHERE id = ?");
$stmt->bind_param("iii", $list_id, $position, $task_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update task position']);
}