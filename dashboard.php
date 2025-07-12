<?php
require_once 'db.php';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }

    if (isset($_POST['register'])) {
        // Registration logic
        $name = $conn->real_escape_string($_POST['name']);
        $email = $conn->real_escape_string($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            header("Location: register.php?error=Passwords don't match");
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $hashed_password);

        if ($stmt->execute()) {
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $name;
        } else {
            header("Location: register.php?error=Email already exists");
            exit;
        }
    } else {
        // Login logic
        $email = $conn->real_escape_string($_POST['email']);
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT id, email, name, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['name'];
            } else {
                header("Location: index.php?error=Invalid credentials");
                exit;
            }
        } else {
            header("Location: index.php?error=Invalid credentials");
            exit;
        }
    }
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Get user's projects
$stmt = $conn->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get selected project (or first one by default)
$selected_project_id = $_GET['project_id'] ?? ($projects[0]['id'] ?? null);
$lists = [];
$tasks = [];

if ($selected_project_id) {
    // Get lists for selected project
    $stmt = $conn->prepare("SELECT * FROM lists WHERE project_id = ? ORDER BY position");
    $stmt->bind_param("i", $selected_project_id);
    $stmt->execute();
    $lists = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get tasks for each list
    foreach ($lists as &$list) {
        $stmt = $conn->prepare("SELECT * FROM tasks WHERE list_id = ? ORDER BY position");
        $stmt->bind_param("i", $list['id']);
        $stmt->execute();
        $list['tasks'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskNest - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-hover: #4f46e5;
            --secondary-color: #f8fafc;
            --accent-color: #f59e0b;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --low-priority: #10b981;
            --medium-priority: #f59e0b;
            --high-priority: #ef4444;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f1f5f9;
            color: var(--text-dark);
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .project-sidebar {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            height: calc(100vh - 120px);
            overflow-y: auto;
        }
        
        .project-sidebar .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 12px 12px 0 0 !important;
        }
        
        .list-group-item {
            border: none;
            padding: 12px 16px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .list-group-item.active {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .kanban-board {
            display: flex;
            gap: 20px;
            padding: 10px;
            overflow-x: auto;
            height: calc(100vh - 120px);
        }
        
        .kanban-list {
            background: white;
            border-radius: 12px;
            padding: 16px;
            min-width: 300px;
            flex: 0 0 auto;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            height: fit-content;
            max-height: 90%;
            display: flex;
            flex-direction: column;
        }
        
        .kanban-task {
            background: white;
            border-radius: 10px;
            padding: 16px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            cursor: grab;
            position: relative;
            transition: all 0.2s;
            border-left: 4px solid var(--medium-priority);
            margin-bottom: 12px;
        }
        
        .task-priority {
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            border-radius: 10px 0 0 10px;
        }
        
        .task-priority.low { background-color: var(--low-priority); }
        .task-priority.medium { background-color: var(--medium-priority); }
        .task-priority.high { background-color: var(--high-priority); }
        
        .modal-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 12px 12px 0 0 !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-kanban"></i>TaskNest
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <div class="me-3 text-white">
                        <i class="bi bi-person-circle me-2"></i>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?>
                    </div>
                    <a href="logout.php" class="btn btn-outline-light">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3">
                <div class="card project-sidebar">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Projects</h5>
                        <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addProjectModal">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($projects as $project): ?>
                            <a href="dashboard.php?project_id=<?= $project['id'] ?>" 
                               class="list-group-item list-group-item-action <?= $project['id'] == $selected_project_id ? 'active' : '' ?>">
                                <?= htmlspecialchars($project['title']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <?php if ($selected_project_id): ?>
                    <div class="kanban-board">
                        <?php foreach ($lists as $list): ?>
                            <div class="kanban-list" data-list-id="<?= $list['id'] ?>">
                                <div class="kanban-list-header">
                                    <h5><?= htmlspecialchars($list['title']) ?></h5>
                                    <button class="btn btn-sm btn-success add-task-btn" data-list-id="<?= $list['id'] ?>">
                                        <i class="bi bi-plus"></i> Add Task
                                    </button>
                                </div>
                                <div class="kanban-list-body" data-list-id="<?= $list['id'] ?>">
                                    <?php foreach ($list['tasks'] as $task): ?>
                                        <div class="kanban-task" data-task-id="<?= $task['id'] ?>" draggable="true">
                                            <div class="task-priority <?= $task['priority'] ?>"></div>
                                            <h6><?= htmlspecialchars($task['title']) ?></h6>
                                            <?php if ($task['description']): ?>
                                                <p class="task-description"><?= htmlspecialchars($task['description']) ?></p>
                                            <?php endif; ?>
                                            <?php if ($task['deadline']): ?>
                                                <div class="task-deadline">
                                                    <i class="bi bi-calendar"></i> 
                                                    <?= date('M j, Y', strtotime($task['deadline'])) ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="task-actions">
                                                <button class="btn btn-sm btn-outline-primary edit-task" 
                                                    data-task-id="<?= $task['id'] ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger delete-task" 
                                                    data-task-id="<?= $task['id'] ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        No projects found. Create your first project to get started!
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Project Modal -->
    <div class="modal fade" id="addProjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addProjectForm">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="projectTitle" class="form-label">Project Title</label>
                            <input type="text" class="form-control" id="projectTitle" name="title" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Project</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add/Edit Task Modal -->
    <div class="modal fade" id="taskModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="taskModalTitle">Add Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="taskForm" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="list_id" id="taskListId">
                    <input type="hidden" name="task_id" id="taskId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="taskTitle" class="form-label">Title</label>
                            <input type="text" class="form-control" id="taskTitle" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="taskDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="taskDescription" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="taskPriority" class="form-label">Priority</label>
                                <select class="form-select" id="taskPriority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="taskDeadline" class="form-label">Deadline</label>
                                <input type="date" class="form-control" id="taskDeadline" name="deadline">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="taskAttachment" class="form-label">Attachment</label>
                            <input type="file" class="form-control" id="taskAttachment" name="attachment">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Add before closing </body> tag -->
<footer class="text-center py-3 text-muted small">
    TaskNest System © <?= date('Y') ?> | Developed by MZ
</footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>