require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'classes/Rental.php';
require_once 'classes/Fine.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$rental = new Rental($db);
$fine = new Fine($db);

$user = getCurrentUser();
$user_rentals = $rental->getUserRentals($_SESSION['user_id']);
$user_fines = $fine->getUserFines($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Lens Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">LensRental</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Halo, <?php echo htmlspecialchars($user['name']); ?>!</span>
                <a class="nav-link" href="index.php">Beranda</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Dashboard</h2>
        
        <!-- Rental History -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Riwayat Penyewaan</h5>
            </div>
            <div class="card-body">
                <?php if ($user_rentals->rowCount() > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Lensa</th>
                                    <th>Tanggal Sewa</th>
                                    <th>Tanggal Kembali</th>
                                    <th>Status</th>
                                    <th>Harga/hari</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $user_rentals->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['lens_name']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['rental_date'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['return_date'])); ?></td>
                                    <td>
                                        <span class="badge <?php echo $row['status'] == 'active' ? 'bg-warning' : 'bg-success'; ?>">
                                            <?php echo $row['status'] == 'active' ? 'Aktif' : 'Dikembalikan'; ?>
                                        </span>
                                    </td>
                                    <td>Rp <?php echo number_format($row['price_per_day'], 0, ',', '.'); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>Belum ada riwayat penyewaan.</p>
                    <a href="index.php" class="btn btn-primary">Mulai Sewa Lensa</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Fines -->
        <div class="card">
            <div class="card-header">
                <h5>Denda</h5>
            </div>
            <div class="card-body">
                <?php if ($user_fines->rowCount() > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Lensa</th>
                                    <th>Periode Sewa</th>
                                    <th>Jumlah Denda</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $user_fines->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['lens_name']); ?></td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($row['rental_date'])); ?> - 
                                        <?php echo date('d/m/Y', strtotime($row['return_date'])); ?>
                                    </td>
                                    <td>Rp <?php echo number_format($row['amount'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge <?php echo $row['status'] == 'pending' ? 'bg-danger' : 'bg-success'; ?>">
                                            <?php echo $row['status'] == 'pending' ? 'Belum Dibayar' : 'Lunas'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>Tidak ada denda.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php