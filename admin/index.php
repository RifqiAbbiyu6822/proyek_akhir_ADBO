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
    if ($fine->create($rental_id, $amount)) {
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
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">LensRental - Admin</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php">Beranda</a>
                <a class="nav-link" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Admin Dashboard</h2>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Active/Overdue Rentals -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Penyewaan Aktif & Terlambat</h5>
            </div>
            <div class="card-body">
                <?php if ($overdue_rentals_error): ?>
                    <div class="alert alert-danger">Error: <?php echo htmlspecialchars($overdue_rentals_error); ?></div>
                <?php elseif ($overdue_rentals && $overdue_rentals->rowCount() > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
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
                                    
                                    // Check for existing fine
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
                                            <span class="badge bg-danger"><?php echo $days_overdue; ?> hari terlambat</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($fine_data): ?>
                                            <span class="badge <?php echo $fine_data['status'] == 'paid' ? 'bg-success' : 'bg-warning'; ?>">
                                                Rp <?php echo number_format($fine_data['amount'], 0, ',', '.'); ?> 
                                                (<?php echo $fine_data['status'] == 'paid' ? 'Lunas' : 'Belum Bayar'; ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Belum ada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <!-- Fine Actions -->
                                            <?php if ($fine_data && $fine_data['status'] == 'pending'): ?>
                                                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" 
                                                        data-bs-target="#payFineModal<?php echo $row['id']; ?>">
                                                    Bayar Denda
                                                </button>
                                            <?php elseif (!$fine_data && $is_overdue): ?>
                                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" 
                                                        data-bs-target="#fineModal<?php echo $row['id']; ?>">
                                                    Buat Denda
                                                </button>
                                            <?php endif; ?>
                                            
                                            <!-- Return Lens Button -->
                                            <?php if (!$fine_data || $fine_data['status'] == 'paid'): ?>
                                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" 
                                                        data-bs-target="#returnModal<?php echo $row['id']; ?>">
                                                    Kembalikan
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
                                        <?php if (!$fine_data && $is_overdue): ?>
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
                                                                    <option value="ringan">Ringan (+Rp 20.000)</option>
                                                                    <option value="sedang">Sedang (+Rp 50.000)</option>
                                                                    <option value="berat">Berat (+Rp 100.000)</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="fine_amount_<?php echo $row['id']; ?>" class="form-label">Jumlah Denda (Rp)</label>
                                                                <input type="number" class="form-control fine-amount" name="fine_amount" id="fine_amount_<?php echo $row['id']; ?>" value="<?php echo $days_overdue * 10000; ?>" min="0" required>
                                                                <small class="form-text text-muted">
                                                                    Saran: Rp <span class="fine-suggestion"><?php echo number_format($days_overdue * 10000, 0, ',', '.'); ?></span> (<?php echo $days_overdue; ?> hari × Rp 10.000 + denda kerusakan)
                                                                </small>
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
        <div class="card">
            <div class="card-header">
                <h5>Manajemen Lensa</h5>
            </div>
            <div class="card-body">
                <?php if ($all_lenses && $all_lenses->rowCount() > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
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
                                        <span class="badge <?php echo $row['status'] == 'available' ? 'bg-success' : 'bg-warning'; ?>">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.modal').forEach(function(modal) {
            const fineDays = modal.querySelector('.fine-days');
            const damageType = modal.querySelector('.damage-type');
            const fineAmount = modal.querySelector('.fine-amount');
            const fineSuggestion = modal.querySelector('.fine-suggestion');
            if (!fineDays || !damageType || !fineAmount || !fineSuggestion) return;
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
                fineSuggestion.textContent = total.toLocaleString('id-ID');
            }
            fineDays.addEventListener('input', updateFine);
            damageType.addEventListener('change', updateFine);
            modal.addEventListener('shown.bs.modal', updateFine);
        });
    });
    </script>
</body>
</html>