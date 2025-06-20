<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'classes/User.php';

// Initialize variables
$error_message = "";
$success_message = "";

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = "Invalid request";
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            $user = new User($db);
            
            $user->name = $_POST['name'] ?? '';
            $user->email = $_POST['email'] ?? '';
            $user->password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if ($user->password !== $confirm_password) {
                $error_message = "Password tidak cocok!";
            } else {
                if ($user->register()) {
                    $success_message = "Registrasi berhasil! Silakan login.";
                }
            }
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Lens Rental</title>
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
        <div class="register-container fade-in">
            <div class="card auth-card">
                <div class="card-header">
                    <h3>
                        <i class="fas fa-user-plus me-3"></i>Register
                    </h3>
                    <p class="text-muted mb-0">Buat akun baru Anda</p>
                </div>
                <div class="card-body p-4">
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                <i class="fas fa-user me-2"></i>Nama Lengkap
                            </label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-2"></i>Email Address
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Password
                            </label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Konfirmasi Password
                            </label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Register
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p class="mb-0">
                            Sudah punya akun? 
                            <a href="login.php" class="text-decoration-none">
                                <i class="fas fa-sign-in-alt me-1"></i>Login di sini
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

    // Password confirmation validation
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    function validatePassword() {
        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Password tidak cocok!');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    password.addEventListener('change', validatePassword);
    confirmPassword.addEventListener('keyup', validatePassword);
    </script>
</body>
</html>

<?php