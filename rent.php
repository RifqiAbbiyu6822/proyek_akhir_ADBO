<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'classes/Lens.php';
require_once 'classes/Rental.php';

requireLogin();
if ($_SESSION['user_id'] == 4) {
    header('Location: admin/index.php');
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $lens_obj = new Lens($db);
    $rental = new Rental($db);

    $lens_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if (!$lens_id) {
        throw new Exception('ID Lensa tidak valid.');
    }

    $lens = $lens_obj->readOne($lens_id);
    if (!$lens) {
        throw new Exception('Lensa tidak ditemukan.');
    }

    if ($lens['status'] !== 'available') {
        throw new Exception('Lensa tidak tersedia untuk disewa.');
    }

    $success_message = "";
    $error_message = "";

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid request');
        }

        $rental_date = $_POST['rental_date'] ?? '';
        $return_date = $_POST['return_date'] ?? '';
        
        // Validate dates
        if (empty($rental_date) || empty($return_date)) {
            throw new Exception('Tanggal sewa dan kembali harus diisi.');
        }

        $rental_timestamp = strtotime($rental_date);
        $return_timestamp = strtotime($return_date);
        $today_timestamp = strtotime(date('Y-m-d'));

        if ($rental_timestamp < $today_timestamp) {
            throw new Exception('Tanggal sewa tidak boleh di masa lalu.');
        }

        if ($rental_timestamp >= $return_timestamp) {
            throw new Exception('Tanggal kembali harus setelah tanggal sewa.');
        }

        // Calculate rental days and total price
        $rental_days = max(1, ceil(($return_timestamp - $rental_timestamp) / (60*60*24)));
        $total_price = $rental_days * $lens['price_per_day'];

        $db->beginTransaction();
        try {
            if ($rental->create($_SESSION['user_id'], $lens_id, $rental_date, $return_date, $total_price)) {
                if ($lens_obj->updateStatus($lens_id, 'rented')) {
                    $db->commit();
                    $success_message = "Penyewaan berhasil! Silakan ambil lensa sesuai jadwal.";
                } else {
                    throw new Exception("Gagal update status lensa");
                }
            } else {
                throw new Exception("Gagal membuat rental");
            }
        } catch (Exception $e) {
            $db->rollback();
            throw new Exception("Terjadi kesalahan: " . $e->getMessage());
        }
    }
} catch (Exception $e) {
    $error_message = $e->getMessage();
    error_log("Rent error: " . $e->getMessage());
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sewa Lensa - <?php echo htmlspecialchars($lens['name']); ?></title>
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
        .card {
            background: var(--card-bg);
            box-shadow: var(--shadow);
            border-radius: 16px;
            border: 1px solid var(--border);
        }
        .card-header {
            border-radius: 16px 16px 0 0 !important;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .table {
            background: transparent;
        }
        .table th, .table td {
            vertical-align: middle;
            border-color: var(--border) !important;
        }
        .table-hover tbody tr:hover {
            background: var(--accent) !important;
            color: #fff;
            transition: background 0.2s, color 0.2s;
        }
        .badge {
            font-size: 0.95em;
            padding: 0.5em 0.8em;
            border-radius: 8px;
            font-weight: 600;
        }
        .btn {
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.2px;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
        }
        .btn-primary {
            background: var(--primary);
            border-color: var(--primary-dark);
        }
        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary);
        }
        .btn-info {
            background: var(--accent);
            border: none;
            color: #fff;
        }
        .btn-info:hover {
            background: #0ea5e9;
        }
        @media (max-width: 576px) {
            .card {
                border-radius: 10px;
            }
            .main-content {
                padding: 0 0.5rem;
            }
            .footer {
                padding: 10px 0 6px 0;
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
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">LensRental</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == 4): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/index.php">Admin Panel</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="navbar-nav">
                    <a class="nav-link" href="logout.php">Logout</a>
                    <button class="theme-toggle" id="themeToggle" title="Toggle dark mode"><i class="fa fa-moon"></i></button>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Sewa Lensa: <?php echo htmlspecialchars($lens['name']); ?></h3>
                    </div>
                    <div class="card-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                            </div>
                            <a href="dashboard.php" class="btn btn-primary">
                                <i class="bi bi-speedometer2"></i> Lihat Dashboard
                            </a>
                        <?php else: ?>
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger">
                                    <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="post" id="rentalForm">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <div class="mb-3">
                                    <label for="rental_date" class="form-label">Tanggal Sewa</label>
                                    <input type="date" class="form-control" id="rental_date" name="rental_date" 
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="return_date" class="form-label">Tanggal Kembali</label>
                                    <input type="date" class="form-control" id="return_date" name="return_date" 
                                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Konfirmasi Penyewaan
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Batal
                                </a>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Detail Lensa</h5>
                    </div>
                    <div class="card-body">
                        <h6><?php echo htmlspecialchars($lens['name']); ?></h6>
                        <p><?php echo htmlspecialchars($lens['description']); ?></p>
                        <p><strong>Harga: Rp <?php echo number_format($lens['price_per_day'], 0, ',', '.'); ?>/hari</strong></p>
                        <hr>
                        <div id="priceCalculation">
                            <p><strong>Estimasi Biaya:</strong></p>
                            <p>Durasi: <span id="days">1</span> hari</p>
                            <p>Total: <strong>Rp <span id="totalPrice"><?php echo number_format($lens['price_per_day'], 0, ',', '.'); ?></span></strong></p>
                        </div>
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

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rentalDate = document.getElementById('rental_date');
            const returnDate = document.getElementById('return_date');
            const daysSpan = document.getElementById('days');
            const totalPriceSpan = document.getElementById('totalPrice');
            const pricePerDay = <?php echo $lens['price_per_day']; ?>;

            function calculatePrice() {
                if (rentalDate.value && returnDate.value) {
                    const start = new Date(rentalDate.value);
                    const end = new Date(returnDate.value);
                    const diffTime = Math.abs(end - start);
                    const diffDays = Math.max(1, Math.ceil(diffTime / (1000 * 60 * 60 * 24)));
                    
                    daysSpan.textContent = diffDays;
                    totalPriceSpan.textContent = (diffDays * pricePerDay).toLocaleString('id-ID');
                    
                    // Update return date minimum
                    const minReturn = new Date(start);
                    minReturn.setDate(minReturn.getDate() + 1);
                    returnDate.min = minReturn.toISOString().split('T')[0];
                }
            }

            rentalDate.addEventListener('change', calculatePrice);
            returnDate.addEventListener('change', calculatePrice);
        });

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