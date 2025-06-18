<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'classes/Lens.php';
require_once 'classes/Rental.php';

requireLogin();

try {
    $database = new Database();
    $db = $database->getConnection();
    $lens_obj = new Lens($db);
    $rental = new Rental($db);

    $lens_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if (!$lens_id) {
        throw new Exception('ID Lensa tidak valid.');
    }

    $lens = $lens_obj->readOne($lens_id);
    if (!$lens) {
        throw new Exception('Lensa tidak ditemukan.');
    }

    if ($lens['status'] !== 'available') {
        throw new Exception('Lensa tidak tersedia untuk disewa.');
    }

    $success_message = "";
    $error_message = "";

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid request');
        }

        $rental_date = $_POST['rental_date'] ?? '';
        $return_date = $_POST['return_date'] ?? '';
        
        // Validate dates
        if (empty($rental_date) || empty($return_date)) {
            throw new Exception('Tanggal sewa dan kembali harus diisi.');
        }

        $rental_timestamp = strtotime($rental_date);
        $return_timestamp = strtotime($return_date);
        $today_timestamp = strtotime(date('Y-m-d'));

        if ($rental_timestamp < $today_timestamp) {
            throw new Exception('Tanggal sewa tidak boleh di masa lalu.');
        }

        if ($rental_timestamp >= $return_timestamp) {
            throw new Exception('Tanggal kembali harus setelah tanggal sewa.');
        }

        // Calculate rental days and total price
        $rental_days = max(1, ceil(($return_timestamp - $rental_timestamp) / (60*60*24)));
        $total_price = $rental_days * $lens['price_per_day'];

        $db->beginTransaction();
        try {
            if ($rental->create($_SESSION['user_id'], $lens_id, $rental_date, $return_date, $total_price)) {
                if ($lens_obj->updateStatus($lens_id, 'rented')) {
                    $db->commit();
                    $success_message = "Penyewaan berhasil! Silakan ambil lensa sesuai jadwal.";
                } else {
                    throw new Exception("Gagal update status lensa");
                }
            } else {
                throw new Exception("Gagal membuat rental");
            }
        } catch (Exception $e) {
            $db->rollback();
            throw new Exception("Terjadi kesalahan: " . $e->getMessage());
        }
    }
} catch (Exception $e) {
    $error_message = $e->getMessage();
    error_log("Rent error: " . $e->getMessage());
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sewa Lensa - <?php echo htmlspecialchars($lens['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
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
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == 4): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/index.php">Admin Panel</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="navbar-nav">
                    <a class="nav-link" href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Sewa Lensa: <?php echo htmlspecialchars($lens['name']); ?></h3>
                    </div>
                    <div class="card-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                            </div>
                            <a href="dashboard.php" class="btn btn-primary">
                                <i class="bi bi-speedometer2"></i> Lihat Dashboard
                            </a>
                        <?php else: ?>
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger">
                                    <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="post" id="rentalForm">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                <div class="mb-3">
                                    <label for="rental_date" class="form-label">Tanggal Sewa</label>
                                    <input type="date" class="form-control" id="rental_date" name="rental_date" 
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="return_date" class="form-label">Tanggal Kembali</label>
                                    <input type="date" class="form-control" id="return_date" name="return_date" 
                                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Konfirmasi Penyewaan
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Batal
                                </a>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Detail Lensa</h5>
                    </div>
                    <div class="card-body">
                        <h6><?php echo htmlspecialchars($lens['name']); ?></h6>
                        <p><?php echo htmlspecialchars($lens['description']); ?></p>
                        <p><strong>Harga: Rp <?php echo number_format($lens['price_per_day'], 0, ',', '.'); ?>/hari</strong></p>
                        <hr>
                        <div id="priceCalculation">
                            <p><strong>Estimasi Biaya:</strong></p>
                            <p>Durasi: <span id="days">1</span> hari</p>
                            <p>Total: <strong>Rp <span id="totalPrice"><?php echo number_format($lens['price_per_day'], 0, ',', '.'); ?></span></strong></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rentalDate = document.getElementById('rental_date');
            const returnDate = document.getElementById('return_date');
            const daysSpan = document.getElementById('days');
            const totalPriceSpan = document.getElementById('totalPrice');
            const pricePerDay = <?php echo $lens['price_per_day']; ?>;

            function calculatePrice() {
                if (rentalDate.value && returnDate.value) {
                    const start = new Date(rentalDate.value);
                    const end = new Date(returnDate.value);
                    const diffTime = Math.abs(end - start);
                    const diffDays = Math.max(1, Math.ceil(diffTime / (1000 * 60 * 60 * 24)));
                    
                    daysSpan.textContent = diffDays;
                    totalPriceSpan.textContent = (diffDays * pricePerDay).toLocaleString('id-ID');
                    
                    // Update return date minimum
                    const minReturn = new Date(start);
                    minReturn.setDate(minReturn.getDate() + 1);
                    returnDate.min = minReturn.toISOString().split('T')[0];
                }
            }

            rentalDate.addEventListener('change', calculatePrice);
            returnDate.addEventListener('change', calculatePrice);
        });
    </script>
</body>
</html>

<?php