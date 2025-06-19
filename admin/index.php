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
        .main-content {
            flex: 1 0 auto;
            padding-bottom: 40px;
        }
        .footer {
            flex-shrink: 0;
            background: var(--card-bg);
            color: var(--text);
            text-align: center;
            padding: 16px 0 8px 0;
            border-top: 1px solid var(--border);
            box-shadow: var(--shadow);
        }
        .navbar {
            background: var(--primary-dark) !important;
            box-shadow: var(--shadow);
        }
        .navbar-brand i {
            margin-right: 8px;
        }
        .navbar .nav-link {
            color: #fff !important;
            font-weight: 500;
            transition: color 0.2s;
        }
        .navbar .nav-link:hover {
            color: var(--accent) !important;
        }
        .card {
            background: var(--card-bg);
            box-shadow: var(--shadow);
            border-radius: 16px;
            border: 1px solid var(--border);
            margin-bottom: 2rem;
            transition: box-shadow 0.2s;
        }
        .card:hover {
            box-shadow: 0 4px 24px rgba(37,99,235,0.08);
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
        .table-striped > tbody > tr:nth-of-type(odd) {
            --bs-table-accent-bg: var(--bg);
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
        .btn-primary, .btn-success, .btn-warning, .btn-info {
            box-shadow: 0 2px 8px rgba(37,99,235,0.08);
        }
        .btn-primary {
            background: var(--primary);
            border-color: var(--primary-dark);
        }
        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary);
        }
        .btn-warning {
            background: #facc15;
            color: #222;
            border: none;
        }
        .btn-warning:hover {
            background: #fde047;
            color: #111;
        }
        .btn-success {
            background: #22c55e;
            border: none;
        }
        .btn-success:hover {
            background: #16a34a;
        }
        .btn-info {
            background: var(--accent);
            border: none;
            color: #fff;
        }
        .btn-info:hover {
            background: #0ea5e9;
        }
        .modal-content {
            border-radius: 16px;
            background: var(--card-bg);
            color: var(--text);
            box-shadow: var(--shadow);
        }
        .modal-header {
            border-bottom: 1px solid var(--border);
        }
        .modal-footer {
            border-top: 1px solid var(--border);
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--bg);
            color: var(--text);
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px var(--accent);
        }
        .fw-bold {
            font-weight: 700 !important;
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
        @media (max-width: 576px) {
            .card, .modal-content {
                border-radius: 10px;
            }
            .main-content {
                padding: 0 0.5rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <i class="fa-solid fa-camera-retro"></i> <span>LensRental <small class="text-warning ms-2">Admin</small></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php"><i class="fa fa-home me-1"></i>Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php"><i class="fa fa-sign-out-alt me-1"></i>Logout</a>
                    </li>
                    <li class="nav-item">
                        <button class="theme-toggle" id="themeToggle" title="Toggle dark mode"><i class="fa fa-moon"></i></button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container main-content mt-4 mb-5">
        <h2 class="mb-4 fw-bold text-center">Admin Dashboard</h2>
        <?php if ($success_message): ?>
            <div class="alert alert-success shadow-sm"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger shadow-sm"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <!-- Active/Overdue Rentals -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white rounded-top">
                <h5 class="mb-0"><i class="fa fa-clock me-2"></i>Penyewaan Aktif & Terlambat</h5>
            </div>
            <div class="card-body">
                <?php if ($overdue_rentals_error): ?>
                    <div class="alert alert-danger">Error: <?php echo htmlspecialchars($overdue_rentals_error); ?></div>
                <?php elseif ($overdue_rentals && $overdue_rentals->rowCount() > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Penyewa</th>
                                    <th>Email</th>
                                    <th>Lensa</th>
                                    <th>Tgl Sewa</th>
                                    <th>Tgl Kembali</th>
                                    <th>Status</th>
                                    <th>Denda</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $overdue_rentals->fetch(PDO::FETCH_ASSOC)): 
                                    $return_date = strtotime($row['return_date']);
                                    $today = strtotime(date('Y-m-d'));
                                    $is_overdue = $today > $return_date;
                                    $days_overdue = $is_overdue ? floor(($today - $return_date) / (60*60*24)) : 0;
                                    $stmt_fine = $db->prepare("SELECT * FROM fines WHERE rental_id = ?");
                                    $stmt_fine->execute([$row['id']]);
                                    $fine_data = $stmt_fine->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <tr class="<?php echo $is_overdue ? 'table-warning' : ''; ?>">
                                    <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['lens_name']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['rental_date'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['return_date'])); ?></td>
                                    <td>
                                        <?php if ($is_overdue): ?>
                                            <span class="badge bg-danger"><i class="fa fa-exclamation-circle me-1"></i><?php echo $days_overdue; ?> hari terlambat</span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><i class="fa fa-check-circle me-1"></i>Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($fine_data): ?>
                                            <span class="badge <?php echo $fine_data['status'] == 'paid' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                                <i class="fa fa-money-bill-wave me-1"></i>Rp <?php echo number_format($fine_data['amount'], 0, ',', '.'); ?> 
                                                (<?php echo $fine_data['status'] == 'paid' ? 'Lunas' : 'Belum Bayar'; ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Belum ada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <?php if ($fine_data && $fine_data['status'] == 'pending'): ?>
                                                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" 
                                                        data-bs-target="#payFineModal<?php echo $row['id']; ?>">
                                                    <i class="fa fa-money-bill"></i> Bayar Denda
                                                </button>
                                            <?php elseif (!$fine_data): ?>
                                                <button type="button" class="btn btn-warning btn-sm text-dark" data-bs-toggle="modal" 
                                                        data-bs-target="#fineModal<?php echo $row['id']; ?>">
                                                    <i class="fa fa-exclamation-triangle"></i> Buat Denda
                                                </button>
                                            <?php endif; ?>
                                            <?php if (!$fine_data || $fine_data['status'] == 'paid'): ?>
                                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" 
                                                        data-bs-target="#returnModal<?php echo $row['id']; ?>">
                                                    <i class="fa fa-undo"></i> Kembalikan
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        <!-- Pay Fine Modal -->
                                        <?php if ($fine_data && $fine_data['status'] == 'pending'): ?>
                                        <div class="modal fade" id="payFineModal<?php echo $row['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Konfirmasi Pembayaran Denda</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <p>Konfirmasi pembayaran denda sebesar <strong>Rp <?php echo number_format($fine_data['amount'], 0, ',', '.'); ?></strong> 
                                                            untuk <strong><?php echo htmlspecialchars($row['user_name']); ?></strong>?</p>
                                                            <input type="hidden" name="fine_id" value="<?php echo $fine_data['id']; ?>">
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" name="pay_fine" class="btn btn-success">Konfirmasi Bayar</button>
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
                                                        <h5 class="modal-title">Kembalikan Lensa</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <p>Apakah Anda yakin ingin mengembalikan lensa <strong><?php echo htmlspecialchars($row['lens_name']); ?></strong>?</p>
                                                            <input type="hidden" name="rental_id" value="<?php echo $row['id']; ?>">
                                                            <input type="hidden" name="lens_id" value="<?php echo $row['lens_id']; ?>">
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" name="return_lens" class="btn btn-success">Ya, Kembalikan</button>
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
                                                        <h5 class="modal-title">Buat Denda</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <p>Buat denda untuk <strong><?php echo htmlspecialchars($row['user_name']); ?></strong></p>
                                                            <div class="mb-3">
                                                                <label for="fine_days_<?php echo $row['id']; ?>" class="form-label">Hari Keterlambatan</label>
                                                                <input type="number" class="form-control fine-days" name="fine_days" id="fine_days_<?php echo $row['id']; ?>" value="<?php echo $days_overdue; ?>" min="0" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="damage_type_<?php echo $row['id']; ?>" class="form-label">Jenis Kerusakan</label>
                                                                <select class="form-select damage-type" name="damage_type" id="damage_type_<?php echo $row['id']; ?>">
                                                                    <option value="none" selected>Tidak Ada</option>
                                                                    <option value="ringan">Ringan</option>
                                                                    <option value="sedang">Sedang</option>
                                                                    <option value="berat">Berat</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="fine_amount_<?php echo $row['id']; ?>" class="form-label">Jumlah Denda (Rp)</label>
                                                                <input type="number" class="form-control fine-amount" name="fine_amount" id="fine_amount_<?php echo $row['id']; ?>" value="" min="0" required>
                                                            </div>
                                                            <input type="hidden" name="rental_id" value="<?php echo $row['id']; ?>">
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" name="create_fine" class="btn btn-warning">Buat Denda</button>
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
                    <p class="text-success">Tidak ada penyewaan aktif saat ini.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Lens Management -->
        <div class="card mt-5 mb-4">
            <div class="card-header bg-info text-white rounded-top">
                <h5 class="mb-0"><i class="fa fa-camera me-2"></i>Manajemen Lensa</h5>
            </div>
            <div class="card-body">
                <?php if ($all_lenses && $all_lenses->rowCount() > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Lensa</th>
                                    <th>Deskripsi</th>
                                    <th>Harga/hari</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $all_lenses->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                    <td>Rp <?php echo number_format($row['price_per_day'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge <?php echo $row['status'] == 'available' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                            <?php echo $row['status'] == 'available' ? 'Tersedia' : 'Disewa'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif ($all_lenses): ?>
                    <p>Tidak ada lensa.</p>
                <?php else: ?>
                    <div class="alert alert-danger">Gagal mengambil data lensa.</div>
                <?php endif; ?>
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
    // On load, set theme from localStorage or system
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