<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../classes/Rental.php';
require_once '../classes/Fine.php';
require_once '../classes/Lens.php';

// Simple admin check - in real application, you'd have proper admin roles
if (!isLoggedIn() || $_SESSION['user_id'] != 4) {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$rental = new Rental($db);
$fine = new Fine($db);
$lens = new Lens($db);

$overdue_rentals_error = '';
$overdue_rentals = false;
$all_lenses = false;
$success_message = '';
$error_message = '';

// Process fine creation
if (isset($_POST['create_fine'])) {
    $rental_id = $_POST['rental_id'];
    $fine_days = isset($_POST['fine_days']) ? (int)$_POST['fine_days'] : 0;
    if ($fine_days < 0) $fine_days = 0;
    $amount = isset($_POST['fine_amount']) ? (int)$_POST['fine_amount'] : 0;
    if ($amount < 0) $amount = 0;
    $damage_type = isset($_POST['damage_type']) ? $_POST['damage_type'] : 'none';
    $description = 'Kerusakan: ' . $damage_type . ', Hari keterlambatan: ' . $fine_days;
    if ($fine->create($rental_id, $amount, $description)) {
        $success_message = "Denda berhasil dibuat!";
    } else {
        $error_message = "Gagal membuat denda!";
    }
}
// Proses pembayaran denda
if (isset($_POST['pay_fine'])) {
    $fine_id = isset($_POST['fine_id']) ? (int)$_POST['fine_id'] : 0;
    if ($fine_id < 1) {
        $error_message = "ID denda tidak valid.";
    } else {
        try {
            $stmt = $db->prepare("SELECT * FROM fines WHERE id = ?");
            $stmt->execute([$fine_id]);
            $fine_row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$fine_row) {
                $error_message = "Denda tidak ditemukan.";
            } elseif ($fine_row['status'] !== 'pending') {
                $error_message = "Denda sudah dibayar atau tidak bisa dibayar.";
            } else {
                $fine->updateStatus($fine_id, 'paid');
                $success_message = "Denda berhasil ditandai sudah dibayar!";
            }
        } catch (Exception $e) {
            $error_message = "Gagal update status denda: " . htmlspecialchars($e->getMessage());
        }
    }
}
// Process lens return
if (isset($_POST['return_lens'])) {
    $rental_id = isset($_POST['rental_id']) ? (int)$_POST['rental_id'] : 0;
    $lens_id = isset($_POST['lens_id']) ? (int)$_POST['lens_id'] : 0;
    if ($rental_id < 1 || $lens_id < 1) {
        $error_message = "ID rental/lensa tidak valid.";
    } else {
        // Cek denda pending
        $stmt = $db->prepare("SELECT id FROM fines WHERE rental_id = ? AND status = 'pending'");
        $stmt->execute([$rental_id]);
        if ($stmt->fetch()) {
            $error_message = "Tidak bisa mengembalikan lensa sebelum denda dibayar.";
        } else {
            $db->beginTransaction();
            try {
                if ($rental->returnLens($rental_id) && $lens->updateStatus($lens_id, 'available')) {
                    $db->commit();
                    $success_message = "Lensa berhasil dikembalikan!";
                } else {
                    throw new Exception("Gagal mengembalikan lensa");
                }
            } catch (Exception $e) {
                $db->rollback();
                $error_message = "Terjadi kesalahan: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}

try {
    $overdue_rentals = $rental->getOverdueRentals();
} catch (Exception $e) {
    $overdue_rentals_error = $e->getMessage();
}
try {
    $all_lenses = $lens->readAll();
} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Lens Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../includes/vintage-theme.css">
    <link rel="icon" type="image/png" href="https://cdn-icons-png.flaticon.com/512/2922/2922017.png">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-camera-retro me-2"></i>LensRental 
                <span class="badge badge-warning ms-2">Admin</span>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php">
                    <i class="fas fa-home me-1"></i>Beranda
                </a>
                <a class="nav-link" href="../dashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
                <a class="nav-link" href="../logout.php">
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
        <!-- Welcome Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card slide-in-left">
                    <div class="card-body text-center">
                        <h2 class="card-title">
                            <i class="fas fa-crown me-3"></i>Admin Dashboard
                        </h2>
                        <p class="card-text text-muted">Kelola sistem penyewaan lensa kamera</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($success_message): ?>
            <div class="alert alert-success fade-in">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger fade-in">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card text-center slide-in-left">
                    <div class="card-body">
                        <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                        <h5 class="card-title">Penyewaan Aktif</h5>
                        <p class="price-display">
                            <?php 
                            $active_count = 0;
                            if ($overdue_rentals) {
                                $active_count = $overdue_rentals->rowCount();
                            }
                            echo $active_count;
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-center slide-in-left">
                    <div class="card-body">
                        <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                        <h5 class="card-title">Terlambat</h5>
                        <p class="price-display">
                            <?php 
                            $overdue_count = 0;
                            if ($overdue_rentals) {
                                $overdue_rentals->execute();
                                while ($row = $overdue_rentals->fetch(PDO::FETCH_ASSOC)) {
                                    $return_date = strtotime($row['return_date']);
                                    $today = strtotime(date('Y-m-d'));
                                    if ($today > $return_date) {
                                        $overdue_count++;
                                    }
                                }
                            }
                            echo $overdue_count;
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-center slide-in-left">
                    <div class="card-body">
                        <i class="fas fa-camera fa-3x text-success mb-3"></i>
                        <h5 class="card-title">Total Lensa</h5>
                        <p class="price-display">
                            <?php 
                            $lens_count = 0;
                            if ($all_lenses) {
                                $lens_count = $all_lenses->rowCount();
                            }
                            echo $lens_count;
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active/Overdue Rentals -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card slide-in-right">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-clock me-2"></i>Penyewaan Aktif & Terlambat
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($overdue_rentals_error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>Error: <?php echo htmlspecialchars($overdue_rentals_error); ?>
                            </div>
                        <?php elseif ($overdue_rentals && $overdue_rentals->rowCount() > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-user me-1"></i>Penyewa</th>
                                            <th><i class="fas fa-envelope me-1"></i>Email</th>
                                            <th><i class="fas fa-camera me-1"></i>Lensa</th>
                                            <th><i class="fas fa-calendar-plus me-1"></i>Tgl Sewa</th>
                                            <th><i class="fas fa-calendar-minus me-1"></i>Tgl Kembali</th>
                                            <th><i class="fas fa-info-circle me-1"></i>Status</th>
                                            <th><i class="fas fa-money-bill-wave me-1"></i>Denda</th>
                                            <th><i class="fas fa-cogs me-1"></i>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $overdue_rentals->execute();
                                        while ($row = $overdue_rentals->fetch(PDO::FETCH_ASSOC)): 
                                            $return_date = strtotime($row['return_date']);
                                            $today = strtotime(date('Y-m-d'));
                                            $is_overdue = $today > $return_date;
                                            $days_overdue = $is_overdue ? floor(($today - $return_date) / (60*60*24)) : 0;
                                            $stmt_fine = $db->prepare("SELECT * FROM fines WHERE rental_id = ?");
                                            $stmt_fine->execute([$row['id']]);
                                            $fine_data = $stmt_fine->fetch(PDO::FETCH_ASSOC);
                                        ?>
                                        <tr class="<?php echo $is_overdue ? 'table-warning' : ''; ?>">
                                            <td>
                                                <i class="fas fa-user me-2"></i>
                                                <?php echo htmlspecialchars($row['user_name']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td>
                                                <i class="fas fa-camera me-2"></i>
                                                <?php echo htmlspecialchars($row['lens_name']); ?>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($row['rental_date'])); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($row['return_date'])); ?></td>
                                            <td>
                                                <?php if ($is_overdue): ?>
                                                    <span class="badge badge-danger">
                                                        <i class="fas fa-exclamation-circle me-1"></i><?php echo $days_overdue; ?> hari terlambat
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check-circle me-1"></i>Aktif
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($fine_data): ?>
                                                    <span class="badge <?php echo $fine_data['status'] == 'paid' ? 'badge-success' : 'badge-warning'; ?>">
                                                        <i class="fas fa-money-bill-wave me-1"></i>Rp <?php echo number_format($fine_data['amount'], 0, ',', '.'); ?> 
                                                        (<?php echo $fine_data['status'] == 'paid' ? 'Lunas' : 'Belum Bayar'; ?>)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Belum ada</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <?php if ($fine_data && $fine_data['status'] == 'pending'): ?>
                                                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" 
                                                                data-bs-target="#payFineModal<?php echo $row['id']; ?>">
                                                            <i class="fas fa-money-bill me-1"></i>Bayar Denda
                                                        </button>
                                                    <?php elseif (!$fine_data): ?>
                                                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" 
                                                                data-bs-target="#fineModal<?php echo $row['id']; ?>">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>Buat Denda
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if (!$fine_data || $fine_data['status'] == 'paid'): ?>
                                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" 
                                                                data-bs-target="#returnModal<?php echo $row['id']; ?>">
                                                            <i class="fas fa-undo me-1"></i>Kembalikan
                                                        </button>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Pay Fine Modal -->
                                                <?php if ($fine_data && $fine_data['status'] == 'pending'): ?>
                                                <div class="modal fade" id="payFineModal<?php echo $row['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">
                                                                    <i class="fas fa-money-bill-wave me-2"></i>Konfirmasi Pembayaran Denda
                                                                </h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form method="post">
                                                                <div class="modal-body">
                                                                    <p>Konfirmasi pembayaran denda sebesar <strong class="price-display">Rp <?php echo number_format($fine_data['amount'], 0, ',', '.'); ?></strong> 
                                                                    untuk <strong><?php echo htmlspecialchars($row['user_name']); ?></strong>?</p>
                                                                    <input type="hidden" name="fine_id" value="<?php echo $fine_data['id']; ?>">
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                        <i class="fas fa-times me-1"></i>Batal
                                                                    </button>
                                                                    <button type="submit" name="pay_fine" class="btn btn-success">
                                                                        <i class="fas fa-check me-1"></i>Konfirmasi Bayar
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>

                                                <!-- Return Modal -->
                                                <div class="modal fade" id="returnModal<?php echo $row['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">
                                                                    <i class="fas fa-undo me-2"></i>Kembalikan Lensa
                                                                </h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form method="post">
                                                                <div class="modal-body">
                                                                    <p>Apakah Anda yakin ingin mengembalikan lensa <strong><?php echo htmlspecialchars($row['lens_name']); ?></strong>?</p>
                                                                    <input type="hidden" name="rental_id" value="<?php echo $row['id']; ?>">
                                                                    <input type="hidden" name="lens_id" value="<?php echo $row['lens_id']; ?>">
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                        <i class="fas fa-times me-1"></i>Batal
                                                                    </button>
                                                                    <button type="submit" name="return_lens" class="btn btn-success">
                                                                        <i class="fas fa-check me-1"></i>Ya, Kembalikan
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Fine Modal -->
                                                <?php if (!$fine_data): ?>
                                                <div class="modal fade" id="fineModal<?php echo $row['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">
                                                                    <i class="fas fa-exclamation-triangle me-2"></i>Buat Denda
                                                                </h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form method="post">
                                                                <div class="modal-body">
                                                                    <p>Buat denda untuk <strong><?php echo htmlspecialchars($row['user_name']); ?></strong></p>
                                                                    <div class="mb-3">
                                                                        <label for="fine_days_<?php echo $row['id']; ?>" class="form-label">
                                                                            <i class="fas fa-calendar me-2"></i>Hari Keterlambatan
                                                                        </label>
                                                                        <input type="number" class="form-control fine-days" name="fine_days" id="fine_days_<?php echo $row['id']; ?>" value="<?php echo $days_overdue; ?>" min="0" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="damage_type_<?php echo $row['id']; ?>" class="form-label">
                                                                            <i class="fas fa-tools me-2"></i>Jenis Kerusakan
                                                                        </label>
                                                                        <select class="form-select damage-type" name="damage_type" id="damage_type_<?php echo $row['id']; ?>">
                                                                            <option value="none" selected>Tidak Ada</option>
                                                                            <option value="ringan">Ringan</option>
                                                                            <option value="sedang">Sedang</option>
                                                                            <option value="berat">Berat</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="fine_amount_<?php echo $row['id']; ?>" class="form-label">
                                                                            <i class="fas fa-money-bill-wave me-2"></i>Jumlah Denda (Rp)
                                                                        </label>
                                                                        <input type="number" class="form-control fine-amount" name="fine_amount" id="fine_amount_<?php echo $row['id']; ?>" value="" min="0" required>
                                                                    </div>
                                                                    <input type="hidden" name="rental_id" value="<?php echo $row['id']; ?>">
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                        <i class="fas fa-times me-1"></i>Batal
                                                                    </button>
                                                                    <button type="submit" name="create_fine" class="btn btn-warning">
                                                                        <i class="fas fa-exclamation-triangle me-1"></i>Buat Denda
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <p class="text-success">Tidak ada penyewaan aktif saat ini.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lens Management -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card slide-in-right">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-camera me-2"></i>Manajemen Lensa
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($all_lenses && $all_lenses->rowCount() > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-camera me-1"></i>Nama Lensa</th>
                                            <th><i class="fas fa-info-circle me-1"></i>Deskripsi</th>
                                            <th><i class="fas fa-money-bill-wave me-1"></i>Harga/hari</th>
                                            <th><i class="fas fa-info-circle me-1"></i>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $all_lenses->execute();
                                        while ($row = $all_lenses->fetch(PDO::FETCH_ASSOC)): 
                                        ?>
                                        <tr>
                                            <td>
                                                <i class="fas fa-camera me-2"></i>
                                                <?php echo htmlspecialchars($row['name']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                                            <td class="price-display"><?php echo number_format($row['price_per_day'], 0, ',', '.'); ?></td>
                                            <td>
                                                <span class="badge <?php echo $row['status'] == 'available' ? 'badge-success' : 'badge-warning'; ?>">
                                                    <i class="fas fa-<?php echo $row['status'] == 'available' ? 'check' : 'clock'; ?> me-1"></i>
                                                    <?php echo $row['status'] == 'available' ? 'Tersedia' : 'Disewa'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php elseif ($all_lenses): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-camera fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Tidak ada lensa.</p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>Gagal mengambil data lensa.
                            </div>
                        <?php endif; ?>
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

    // Fine calculation
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.modal').forEach(function(modal) {
            const fineDays = modal.querySelector('.fine-days');
            const damageType = modal.querySelector('.damage-type');
            const fineAmount = modal.querySelector('.fine-amount');
            if (!fineDays || !damageType || !fineAmount) return;
            
            function updateFine() {
                let days = parseInt(fineDays.value) || 0;
                if (days < 0) {
                    days = 0;
                    fineDays.value = 0;
                }
                let base = days * 10000;
                let damage = 0;
                switch (damageType.value) {
                    case 'ringan': damage = 20000; break;
                    case 'sedang': damage = 50000; break;
                    case 'berat': damage = 100000; break;
                    default: damage = 0;
                }
                let total = base + damage;
                fineAmount.value = total;
            }
            
            fineDays.addEventListener('input', updateFine);
            damageType.addEventListener('change', updateFine);
            modal.addEventListener('shown.bs.modal', updateFine);
        });
    });
    </script>
</body>
</html>