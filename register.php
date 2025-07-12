<?php
require_once 'db.php';

// Initialize error array
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }

    // Sanitize inputs
    $name = trim($conn->real_escape_string($_POST['name'] ?? ''));
    $email = trim($conn->real_escape_string($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate inputs
    if (empty($name)) {
        $errors[] = "Name is required";
    }

    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Check if email exists only if no errors so far
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $errors[] = "Email already exists";
        }
        $stmt->close();
    }

    // If no errors, register user
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $hashed_password);

        if ($stmt->execute()) {
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $name;
            $stmt->close();
            header("Location: dashboard.php");
            exit;
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | TaskNest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .auth-container {
            max-width: 500px;
            margin: 0 auto;
        }
        .auth-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .auth-header {
            background-color: #6366f1;
            color: white;
            padding: 1.5rem;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        .auth-body {
            padding: 2rem;
            background-color: white;
            border-radius: 0 0 10px 10px;
        }
        .form-control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.25);
        }
        .btn-primary {
            background-color: #6366f1;
            border: none;
            padding: 10px;
        }
        .btn-primary:hover {
            background-color: #4f46e5;
        }
        .password-feedback {
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body class="d-flex align-items-center min-vh-100 py-4">
    <div class="container">
        <div class="auth-container">
            <div class="auth-card card">
                <div class="auth-header">
                    <h2><i class="bi bi-person-plus me-2"></i>Create Account</h2>
                </div>
                <div class="auth-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <div><?= htmlspecialchars($error) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <input type="hidden" name="register" value="1">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" required
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required
                                   minlength="8">
                            <div class="password-feedback text-muted">Minimum 8 characters</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 py-2">
                            <i class="bi bi-person-plus me-2"></i>Register
                        </button>
                    </form>
                    
                    <div class="text-center mt-3">
                        Already have an account? <a href="index.php">Sign in here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Add before closing </body> tag -->
<footer class="text-center py-3 text-muted small">
    TaskNest System © <?= date('Y') ?> | Developed by MZ
</footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Client-side password validation
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            function checkPasswordMatch() {
                if (password.value && confirmPassword.value) {
                    if (password.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity("Passwords do not match");
                    } else {
                        confirmPassword.setCustomValidity("");
                    }
                }
            }
            
            password.addEventListener('input', checkPasswordMatch);
            confirmPassword.addEventListener('input', checkPasswordMatch);
        });
    </script>
</body>
</html>