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
    } catch (Exception $e) {
        throw $e;
    }

    try {
        $user_fines = $fine->getUserFines($_SESSION['user_id']);
    } catch (Exception $e) {
        throw $e;
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
    $total_fine = 0;
    if ($user_rentals) {
        foreach ($user_rentals as $r) {
            $total_rental += isset($r['total_price']) ? $r['total_price'] : 0;
        }
    }
    if ($user_fines) {
        foreach ($user_fines as $f) {
            $total_fine += isset($f['amount']) ? $f['amount'] : 0;
        }
    }
    $total_tagihan = $total_rental + $total_fine;
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
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
                    <?php if ($user_rentals && $user_rentals->rowCount() > 0): ?>
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
                                    <?php while ($row = $user_rentals->fetch(PDO::FETCH_ASSOC)): ?>
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
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Tidak ada penyewaan aktif.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Fines -->
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Denda</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                    <?php endif; ?>
                    <p class="text-muted">Denda bisa bernilai 0 jika pengembalian tepat waktu.</p>
                    <?php if ($user_fines && $user_fines->rowCount() > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Jumlah</th>
                                        <th>Status</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $user_fines->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                            <td>Rp <?php echo number_format($row['amount'], 0, ',', '.'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $row['status'] === 'paid' ? 'success' : 'warning'; ?>">
                                                    <?php echo htmlspecialchars($row['status']); ?>
                                                </span>
                                                <?php if ($row['status'] == 'pending'): ?>
                                                    <form method="post" style="display:inline;">
                                                        <input type="hidden" name="fine_id" value="<?php echo $row['id']; ?>">
                                                        <button type="submit" name="pay_fine" class="btn btn-success btn-sm">Bayar Denda</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['description'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Tidak ada denda.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="alert alert-info mt-3">
                <strong>Total Tagihan Anda:</strong> Rp <?php echo number_format($total_tagihan, 0, ',', '.'); ?>
                <br><small>(Total harga penyewaan: Rp <?php echo number_format($total_rental, 0, ',', '.'); ?>, Total denda: Rp <?php echo number_format($total_fine, 0, ',', '.'); ?>)</small>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php