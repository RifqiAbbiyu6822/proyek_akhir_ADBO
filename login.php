<?php
require_once 'includes/auth.php';

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    if ($_SESSION['user_id'] == 4) {
        header('Location: admin/index.php');
        exit();
    } else {
        header('Location: dashboard.php');
        exit();
    }
}

$error = '';
$email = '';

// Check for timeout message
if (isset($_GET['timeout'])) {
    $error = 'Your session has expired. Please login again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        require_once 'config/database.php';
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "SELECT id, name, email, password FROM users WHERE email = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['last_activity'] = time();
                $_SESSION['last_regeneration'] = time();
                
                // Redirect to dashboard or saved URL
                $redirect = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
                unset($_SESSION['redirect_after_login']);
                header("Location: " . $redirect);
                exit();
            } else {
                $error = 'Invalid email or password';
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'An error occurred. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Lens Rental System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="https://cdn-icons-png.flaticon.com/512/2922/2922017.png">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --accent: #38bdf8;
            --bg: #f8fafc;
            --card-bg: #fff;
            --text: #222;
            --border: #e5e7eb;
            --shadow: 0 2px 16px rgba(0,0,0,0.06);
        }
        [data-theme="dark"] {
            --primary: #60a5fa;
            --primary-dark: #2563eb;
            --accent: #38bdf8;
            --bg: #181a20;
            --card-bg: #23262f;
            --text: #f3f4f6;
            --border: #2d2f36;
            --shadow: 0 2px 16px rgba(0,0,0,0.18);
        }
        html, body {
            font-family: 'Inter', Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1030;
            background: var(--primary-dark) !important;
            box-shadow: var(--shadow);
        }
        .navbar .nav-link {
            color: #fff !important;
            font-weight: 500;
            transition: color 0.2s;
        }
        .navbar .nav-link:hover {
            color: var(--accent) !important;
        }
        .theme-toggle {
            background: none;
            border: none;
            color: #fff;
            font-size: 1.3em;
            margin-left: 1rem;
            cursor: pointer;
            transition: color 0.2s;
        }
        .theme-toggle:hover {
            color: var(--accent);
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
        }
        .card {
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: var(--shadow);
            background: var(--card-bg);
        }
        .card-header {
            background: var(--card-bg);
            border-bottom: none;
            text-align: center;
            padding: 20px;
        }
        .form-control {
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--bg);
            color: var(--text);
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px var(--accent);
        }
        .btn-primary {
            background: var(--primary);
            border-color: var(--primary-dark);
        }
        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary);
        }
        .alert {
            border-radius: 8px;
        }
        @media (max-width: 576px) {
            .card {
                border-radius: 10px;
            }
        }
        .footer {
            position: fixed;
            left: 0;
            bottom: 0;
            width: 100%;
            background: var(--card-bg);
            color: var(--text);
            text-align: center;
            padding: 16px 0 8px 0;
            border-top: 1px solid var(--border);
            box-shadow: var(--shadow);
        }
        body {
            padding-bottom: 60px; /* Height of footer */
        }
        @media (max-width: 576px) {
            .footer {
                padding: 10px 0 6px 0;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">LensRental</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="login.php">Login</a>
                <a class="nav-link" href="register.php">Register</a>
                <button class="theme-toggle" id="themeToggle" title="Toggle dark mode"><i class="fa fa-moon"></i></button>
            </div>
        </div>
    </nav>
    <div class="container">
        <div class="login-container">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Login</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Login
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p class="mb-0">Don't have an account? <a href="register.php">Register here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer mt-auto">
        <div class="container">
            <span>&copy; <?php echo date('Y'); ?> LensRental. All rights reserved.</span>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
    // Dark mode toggle
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;
    function setTheme(theme) {
        html.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        themeToggle.innerHTML = theme === 'dark' ? '<i class="fa fa-sun"></i>' : '<i class="fa fa-moon"></i>';
    }
    themeToggle.addEventListener('click', function() {
        const current = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        setTheme(current);
    });
    (function() {
        const saved = localStorage.getItem('theme');
        if (saved) {
            setTheme(saved);
        } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            setTheme('dark');
        } else {
            setTheme('light');
        }
    })();
    </script>
</body>
</html>

<?php