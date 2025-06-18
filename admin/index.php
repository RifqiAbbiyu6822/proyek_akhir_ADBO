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

// Get overdue rentals for fine calculation
$overdue_rentals = false;
$overdue_rentals_error = '';
try {
    $overdue_rentals = $rental->getOverdueRentals();
} catch (Exception $e) {
    $overdue_rentals_error = $e->getMessage();
}
$all_lenses = $lens->readAll();

// Process fine creation
if (isset($_POST['create_fine'])) {
    $rental_id = $_POST['rental_id'];
    $amount = $_POST['fine_amount'];
    
    if ($fine->create($rental_id, $amount)) {
        $success_message = "Denda berhasil dibuat!";
    } else {
        $error_message = "Gagal membuat denda!";
    }
}

// Process lens return
if (isset($_POST['return_lens'])) {
    $rental_id = $_POST['rental_id'];
    $lens_id = $_POST['lens_id'];
    
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
        $error_message = "Terjadi kesalahan: " . $e->getMessage();
    }
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
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Overdue Rentals -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Penyewaan Terlambat</h5>
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
                                    <th>Tgl Kembali</th>
                                    <th>Hari Terlambat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $overdue_rentals->fetch(PDO::FETCH_ASSOC)): 
                                    $days_overdue = (strtotime('now') - strtotime($row['return_date'])) / (60*60*24);
                                    $days_overdue = floor($days_overdue);
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['lens_name']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['return_date'])); ?></td>
                                    <td><span class="badge bg-danger"><?php echo $days_overdue; ?> hari</span></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <!-- Return Lens Button -->
                                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" 
                                                    data-bs-target="#returnModal<?php echo $row['id']; ?>">
                                                Kembalikan
                                            </button>
                                            <!-- Fine Button -->
                                            <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" 
                                                    data-bs-target="#fineModal<?php echo $row['id']; ?>">
                                                Buat Denda
                                            </button>
                                        </div>

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
                                                                <input type="number" class="form-control fine-days" name="fine_days" id="fine_days_<?php echo $row['id']; ?>" value="<?php echo $days_overdue; ?>" min="1" required>
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
                                                                <input type="number" class="form-control fine-amount" name="fine_amount" id="fine_amount_<?php echo $row['id']; ?>" value="<?php echo $days_overdue * 10000; ?>" required>
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
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-success">Tidak ada penyewaan yang terlambat.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Lens Management -->
        <div class="card">
            <div class="card-header">
                <h5>Manajemen Lensa</h5>
            </div>
            <div class="card-body">
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
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('fineModal<?php echo $row['id']; ?>');
        if (!modal) return;
        const fineDays = modal.querySelector('.fine-days');
        const damageType = modal.querySelector('.damage-type');
        const fineAmount = modal.querySelector('.fine-amount');
        const fineSuggestion = modal.querySelector('.fine-suggestion');
        function updateFine() {
            let days = parseInt(fineDays.value) || 0;
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
    });
    </script>
</body>
</html>

<?php