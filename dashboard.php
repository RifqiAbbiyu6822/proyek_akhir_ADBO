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
    <link rel="stylesheet" href="includes/vintage-theme.css">
    <link rel="icon" type="image/png" href="https://cdn-icons-png.flaticon.com/512/2922/2922017.png">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-camera-retro me-2"></i>LensRental
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home me-1"></i>Home
                </a>
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == 4): ?>
                    <a class="nav-link" href="admin/index.php">
                        <i class="fas fa-cog me-1"></i>Admin Panel
                    </a>
                <?php endif; ?>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
                <button class="theme-toggle" id="themeToggle" title="Toggle dark mode">
                    <i class="fa fa-moon"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger fade-in">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success fade-in">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card slide-in-left">
                    <div class="card-body text-center">
                        <h2 class="card-title">
                            <i class="fas fa-user-circle me-3"></i>Selamat Datang, <?php echo htmlspecialchars($user['name']); ?>!
                        </h2>
                        <p class="card-text text-muted">Kelola penyewaan lensa Anda di sini</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card text-center slide-in-left">
                    <div class="card-body">
                        <i class="fas fa-camera fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Total Penyewaan</h5>
                        <p class="price-display"><?php echo count($user_rentals); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-center slide-in-left">
                    <div class="card-body">
                        <i class="fas fa-money-bill-wave fa-3x text-success mb-3"></i>
                        <h5 class="card-title">Total Tagihan</h5>
                        <p class="price-display"><?php echo number_format($total_tagihan, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-center slide-in-left">
                    <div class="card-body">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h5 class="card-title">Total Denda</h5>
                        <p class="price-display"><?php echo number_format($total_fine, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Current Rentals -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card slide-in-right">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-clock me-2"></i>Penyewaan Aktif
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($user_rentals)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Tidak ada penyewaan aktif saat ini.</p>
                                <a href="index.php" class="btn btn-primary">
                                    <i class="fas fa-camera me-2"></i>Sewa Lensa
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Lensa</th>
                                            <th>Tanggal Sewa</th>
                                            <th>Tanggal Kembali</th>
                                            <th>Durasi</th>
                                            <th>Total Harga</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($user_rentals as $rental): ?>
                                        <tr>
                                            <td>
                                                <i class="fas fa-camera me-2"></i>
                                                <?php echo htmlspecialchars($rental['lens_name']); ?>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($rental['rental_date'])); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($rental['return_date'])); ?></td>
                                            <td><?php echo $rental['duration']; ?> hari</td>
                                            <td class="price-display"><?php echo number_format($rental['total_price'], 0, ',', '.'); ?></td>
                                            <td>
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check-circle me-1"></i>Aktif
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rental History -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card slide-in-right">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-history me-2"></i>Riwayat Penyewaan
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($user_rental_history)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Belum ada riwayat penyewaan.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Lensa</th>
                                            <th>Tanggal Sewa</th>
                                            <th>Tanggal Kembali</th>
                                            <th>Durasi</th>
                                            <th>Total Harga</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($user_rental_history as $rental): ?>
                                        <tr>
                                            <td>
                                                <i class="fas fa-camera me-2"></i>
                                                <?php echo htmlspecialchars($rental['lens_name']); ?>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($rental['rental_date'])); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($rental['return_date'])); ?></td>
                                            <td><?php echo $rental['duration']; ?> hari</td>
                                            <td class="price-display"><?php echo number_format($rental['total_price'], 0, ',', '.'); ?></td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <i class="fas fa-check me-1"></i>Selesai
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fines Section -->
        <?php if (!empty($user_fines_data)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card slide-in-right">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>Denda
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Lensa</th>
                                        <th>Alasan</th>
                                        <th>Jumlah Denda</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($user_fines_data as $fine): ?>
                                    <tr>
                                        <td>
                                            <i class="fas fa-camera me-2"></i>
                                            <?php echo htmlspecialchars($fine['lens_name']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($fine['reason']); ?></td>
                                        <td class="price-display"><?php echo number_format($fine['amount'], 0, ',', '.'); ?></td>
                                        <td>
                                            <?php if ($fine['status'] === 'pending'): ?>
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-clock me-1"></i>Pending
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check me-1"></i>Dibayar
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($fine['status'] === 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="fine_id" value="<?php echo $fine['id']; ?>">
                                                    <button type="submit" name="pay_fine" class="btn btn-success btn-sm">
                                                        <i class="fas fa-credit-card me-1"></i>Bayar
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">Sudah dibayar</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
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

    // Add scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe all cards for animation
    document.querySelectorAll('.card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });

    // Auto-refresh page after 30 seconds to update data
    setTimeout(() => {
        location.reload();
    }, 30000);
    </script>
</body>
</html>

<?php