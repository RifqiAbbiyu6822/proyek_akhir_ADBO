require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'classes/Lens.php';
require_once 'classes/Rental.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$lens_obj = new Lens($db);
$rental = new Rental($db);

$lens_id = isset($_GET['id']) ? $_GET['id'] : die('ERROR: Lens ID tidak ditemukan.');
$lens = $lens_obj->readOne($lens_id);

if (!$lens || $lens['status'] !== 'available') {
    die('ERROR: Lensa tidak tersedia.');
}

$success_message = "";
$error_message = "";

if ($_POST) {
    $rental_date = $_POST['rental_date'];
    $return_date = $_POST['return_date'];
    
    if (strtotime($rental_date) >= strtotime($return_date)) {
        $error_message = "Tanggal kembali harus setelah tanggal sewa!";
    } else {
        $db->beginTransaction();
        try {
            if ($rental->create($_SESSION['user_id'], $lens_id, $rental_date, $return_date)) {
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
            $error_message = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

// Calculate total days and price
$rental_days = 1;
$total_price = $lens['price_per_day'];

if (isset($_POST['rental_date']) && isset($_POST['return_date'])) {
    $rental_days = max(1, (strtotime($_POST['return_date']) - strtotime($_POST['rental_date'])) / (60*60*24));
    $total_price = $rental_days * $lens['price_per_day'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sewa Lensa - <?php echo htmlspecialchars($lens['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">LensRental</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="logout.php">Logout</a>
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
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                            <a href="dashboard.php" class="btn btn-primary">Lihat Dashboard</a>
                        <?php else: ?>
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger"><?php echo $error_message; ?></div>
                            <?php endif; ?>
                            
                            <form method="post" id="rentalForm">
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
                                <button type="submit" class="btn btn-primary">Konfirmasi Penyewaan</button>
                                <a href="index.php" class="btn btn-secondary">Batal</a>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Detail Lensa</h5>
                    </div>
                    <div class="card-body">
                        <h6><?php echo htmlspecialchars($lens['name']); ?></h6>
                        <p><?php echo htmlspecialchars($lens['description']); ?></p>
                        <p><strong>Harga: Rp <?php echo number_format($lens['price_per_day'], 0, ',', '.'); ?>/hari</strong></p>
                        <hr>
                        <div id="priceCalculation">
                            <p><strong>Estimasi Biaya:</strong></p>
                            <p>Durasi: <span id="days"><?php echo $rental_days; ?></span> hari</p>
                            <p>Total: <strong>Rp <span id="totalPrice"><?php echo number_format($total_price, 0, ',', '.'); ?></span></strong></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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