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
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Register</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success_message): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nama</label>
                                <input type="text" class="form-control" id="name" name="name" required minlength="2">
                                <div class="form-text">Minimal 2 karakter</div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="8">
                                <div class="form-text">Minimal 8 karakter, harus mengandung angka dan huruf</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Register</button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php