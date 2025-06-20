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
    <link rel="stylesheet" href="includes/vintage-theme.css">
    <link rel="icon" type="image/png" href="https://cdn-icons-png.flaticon.com/512/2922/2922017.png">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-camera-retro me-2"></i>LensRental
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="login.php">
                    <i class="fas fa-sign-in-alt me-1"></i>Login
                </a>
                <a class="nav-link" href="register.php">
                    <i class="fas fa-user-plus me-1"></i>Register
                </a>
                <button class="theme-toggle" id="themeToggle" title="Toggle dark mode">
                    <i class="fa fa-moon"></i>
                </button>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="login-container fade-in">
            <div class="card auth-card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-sign-in-alt me-3"></i>Login
                    </h3>
                    <p class="text-muted mb-0">Masuk ke akun Anda</p>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-2"></i>Email Address
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Password
                            </label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p class="mb-0">
                            Belum punya akun? 
                            <a href="register.php" class="text-decoration-none">
                                <i class="fas fa-user-plus me-1"></i>Register di sini
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <span>&copy; <?php echo date('Y'); ?> LensRental. All rights reserved.</span>
                </div>
                <div class="col-md-6 text-end">
                    <span>Made with <i class="fas fa-heart text-danger"></i> for photographers</span>
                </div>
            </div>
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

    // Add form focus effects
    document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
            this.parentElement.style.transition = 'transform 0.3s ease';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });
    </script>
</body>
</html>

<?php