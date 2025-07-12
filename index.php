<?php require_once 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskNest - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-hover: #4f46e5;
            --text-dark: #1e293b;
            --text-light: #64748b;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
            height: 100vh;
            display: flex;
            align-items: center;
            background-image: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
        }
        
        .auth-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .auth-card:hover {
            transform: translateY(-5px);
        }
        
        .auth-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        
        .auth-header h2 {
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .auth-body {
            padding: 2rem;
            background: white;
        }
        
        .form-control {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.25);
        }
        
        .btn-login {
            background-color: var(--primary-color);
            border: none;
            padding: 12px;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-light);
        }
        
        .auth-footer a {
            color: var(--primary-color);
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .auth-footer a:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }
        
        .alert-danger {
            border-radius: 8px;
        }
    </style>
</head>
<body class="auth-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="auth-card">
                    <div class="auth-header">
                        <h2>
                            <i class="bi bi-kanban-fill"></i> TaskNest
                        </h2>
                    </div>
                    <div class="auth-body">
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger mb-4"><?= htmlspecialchars($_GET['error']) ?></div>
                        <?php endif; ?>
                        <form action="dashboard.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <div class="mb-4">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required placeholder="Enter your email">
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required placeholder="Enter your password">
                            </div>
                            <button type="submit" class="btn btn-login btn-primary w-100 py-2">
                                Login <i class="bi bi-arrow-right-short"></i>
                            </button>
                        </form>
                        <div class="auth-footer mt-4">
                            Don't have an account? <a href="register.php">Sign up here</a>
                        </div>
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
</body>
</html>