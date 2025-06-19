<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'classes/Rental.php';
require_once 'classes/Fine.php';

// Ensure user is logged in
requireLogin();

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();

    // Initialize objects
    $rental = new Rental($db);
    $fine = new Fine($db);

    // Get current user data
    $user = getCurrentUser();
    if (!$user) {
        throw new Exception("User data not found");
    }

    // Get user's rentals and fines
    try {
        $user_rentals = $rental->getUserRentals($_SESSION['user_id']);
        $user_rental_history = $rental->getUserRentalHistory($_SESSION['user_id']);
    } catch (Exception $e) {
        throw $e;
    }

    try {
        $user_fines = $fine->getUserFines($_SESSION['user_id']);
    } catch (Exception $e) {
        throw $e;
    }

    // Setelah query $user_fines, fetch semua ke array
    $user_fines_data = [];
    if ($user_fines) {
        while ($row = $user_fines->fetch(PDO::FETCH_ASSOC)) {
            $user_fines_data[] = $row;
        }
    }
    // Hitung total denda
    $total_fine = 0;
    foreach ($user_fines_data as $f) {
        $total_fine += isset($f['amount']) ? $f['amount'] : 0;
    }

    // Proses pembayaran denda oleh user
    if (isset($_POST['pay_fine'])) {
        $fine_id = isset($_POST['fine_id']) ? (int)$_POST['fine_id'] : 0;
        if ($fine_id < 1) {
            $error_message = "ID denda tidak valid.";
        } else {
            try {
                // Pastikan denda milik user dan status pending
                $stmt = $db->prepare("SELECT * FROM fines f JOIN rentals r ON f.rental_id = r.id WHERE f.id = ? AND r.user_id = ?");
                $stmt->execute([$fine_id, $_SESSION['user_id']]);
                $fine_row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$fine_row) {
                    $error_message = "Denda tidak ditemukan atau bukan milik Anda.";
                } elseif ($fine_row['status'] !== 'pending') {
                    $error_message = "Denda sudah dibayar atau tidak bisa dibayar.";
                } else {
                    $fine->updateStatus($fine_id, 'paid');
                    $success_message = "Denda berhasil dibayar!";
                }
            } catch (Exception $e) {
                $error_message = "Gagal membayar denda: " . htmlspecialchars($e->getMessage());
            }
        }
    }

    // Hitung total harga penyewaan dan total denda user
    $total_rental = 0;
    $total_tagihan = 0;
    if ($user_rentals) {
        foreach ($user_rentals as $r) {
            $total_rental += isset($r['total_price']) ? $r['total_price'] : 0;
        }
    }
    // Cek apakah semua denda sudah paid
    $all_fines_paid = true;
    foreach ($user_fines_data as $f) {
        if ($f['status'] !== 'paid') {
            $all_fines_paid = false;
            break;
        }
    }
    if ($all_fines_paid && count($user_fines_data) > 0) {
        $total_tagihan = 0;
    } else {
        $total_tagihan = $total_rental + $total_fine;
    }
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $error_message = "Terjadi kesalahan saat memuat data. Silakan coba lagi nanti.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Lens Rental</title>
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
    <!-- Navigation -->
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
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == 4): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/index.php">Admin Panel</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="navbar-nav">
                    <span class="nav-link">Halo, <?php echo htmlspecialchars($user['name']); ?>!</span>
                    <a class="nav-link" href="logout.php">Logout</a>
                    <button class="theme-toggle" id="themeToggle" title="Toggle dark mode"><i class="fa fa-moon"></i></button>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php else: ?>
            <h2>Dashboard</h2>
            
            <!-- Active Rentals -->
            <div class="card mb-4">
                <?php if ($_SESSION['user_id'] != 4): ?>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Penyewaan Aktif</h4>
                    <a href="index.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle"></i> Sewa Baru
                    </a>
                </div>
                <?php else: ?>
                <div class="card-header">
                    <h4 class="mb-0">Penyewaan Aktif</h4>
                    <span class="badge bg-info">Admin tidak dapat menyewa lensa</span>
                </div>
                <?php endif; ?>
                <div class="card-body">
                    <?php
                    $user_rentals->execute();
                    $active_rows = [];
                    while ($row = $user_rentals->fetch(PDO::FETCH_ASSOC)) {
                        if ($row['status'] === 'active') {
                            $active_rows[] = $row;
                        }
                    }
                    ?>
                    <?php if (count($active_rows) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Lensa</th>
                                        <th>Tanggal Sewa</th>
                                        <th>Tanggal Kembali</th>
                                        <th>Status</th>
                                        <th>Total Biaya</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($active_rows as $row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['lens_name']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($row['rental_date'])); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($row['return_date'])); ?></td>
                                            <td>
                                                <span class="badge bg-success">Aktif</span>
                                            </td>
                                            <td>Rp <?php echo number_format($row['total_price'], 0, ',', '.'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Tidak ada penyewaan aktif.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="alert alert-info mt-3">
                <strong>Total Tagihan Anda:</strong> Rp <?php echo number_format($total_tagihan, 0, ',', '.'); ?>
                <br><small>(Total harga penyewaan: Rp <?php echo number_format($total_rental, 0, ',', '.'); ?>, Total denda: Rp <?php echo number_format($total_fine, 0, ',', '.'); ?>)</small>
            </div>

            <!-- Rental History -->
            <div class="card mt-4">
                <div class="card-header">
                    <h4 class="mb-0">Riwayat Penyewaan</h4>
                </div>
                <div class="card-body">
                    <?php
                    $user_rental_history->execute();
                    $history_rows = [];
                    while ($row = $user_rental_history->fetch(PDO::FETCH_ASSOC)) {
                        $history_rows[] = $row;
                    }
                    ?>
                    <?php if (count($history_rows) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Lensa</th>
                                        <th>Tanggal Sewa</th>
                                        <th>Tanggal Kembali</th>
                                        <th>Status</th>
                                        <th>Total Biaya</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($history_rows as $row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['lens_name']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($row['rental_date'])); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($row['return_date'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $row['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo htmlspecialchars($row['status']); ?>
                                                </span>
                                            </td>
                                            <td>Rp <?php echo number_format($row['total_price'], 0, ',', '.'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Belum ada riwayat penyewaan.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
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