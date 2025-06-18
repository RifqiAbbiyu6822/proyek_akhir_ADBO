<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'classes/Rental.php';
require_once 'classes/Fine.php';

// Debug: Cek session
var_dump($_SESSION);
echo "<hr>";

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

    // Debug: Cek user_id
    echo "User ID: " . $_SESSION['user_id'] . "<hr>";

    // Get user's rentals and fines
    try {
        $user_rentals = $rental->getUserRentals($_SESSION['user_id']);
        // Debug: Cek hasil query rentals
        echo "Jumlah rentals: " . $user_rentals->rowCount() . "<hr>";
    } catch (Exception $e) {
        echo "Error rentals: " . $e->getMessage() . "<hr>";
        throw $e;
    }

    try {
        $user_fines = $fine->getUserFines($_SESSION['user_id']);
        // Debug: Cek hasil query fines
        echo "Jumlah fines: " . $user_fines->rowCount() . "<hr>";
    } catch (Exception $e) {
        echo "Error fines: " . $e->getMessage() . "<hr>";
        throw $e;
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Penyewaan Aktif</h4>
                    <a href="index.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle"></i> Sewa Baru
                    </a>
                </div>
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
                                            <td><?php echo date('d/m/Y', strtotime($row['rent_date'])); ?></td>
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
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php