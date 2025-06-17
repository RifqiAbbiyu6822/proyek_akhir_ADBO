<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'classes/Rental.php';
require_once 'classes/Fine.php';

// Ensure user is logged in
requireLogin();

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize objects
$rental = new Rental($db);
$fine = new Fine($db);

// Get current user data
$user = getCurrentUser();

// Get user's rentals and fines
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
                </ul>
                <div class="navbar-nav">
                    <span class="nav-link">Halo, <?php echo htmlspecialchars($user['name']); ?>!</span>
                    <a class="nav-link" href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Dashboard</h2>
        
        <!-- Active Rentals -->
        <div class="card mb-4">
            <div class="card-header">
                <h4>Penyewaan Aktif</h4>
            </div>
            <div class="card-body">
                <?php if ($user_rentals && $user_rentals->rowCount() > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Lensa</th>
                                    <th>Tanggal Sewa</th>
                                    <th>Tanggal Kembali</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $user_rentals->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['lens_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['rent_date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['return_date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['status']); ?></td>
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
                <h4>Denda</h4>
            </div>
            <div class="card-body">
                <?php if ($user_fines && $user_fines->rowCount() > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jumlah</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $user_fines->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['date']); ?></td>
                                        <td>Rp <?php echo number_format($row['amount'], 0, ',', '.'); ?></td>
                                        <td><?php echo htmlspecialchars($row['status']); ?></td>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php