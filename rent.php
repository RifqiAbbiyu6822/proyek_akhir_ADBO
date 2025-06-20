<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'classes/Lens.php';
require_once 'classes/Rental.php';

requireLogin();
if ($_SESSION['user_id'] == 4) {
    header('Location: admin/index.php');
    exit();
}

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="includes/vintage-theme.css">
    <link rel="icon" type="image/png" href="https://cdn-icons-png.flaticon.com/512/2922/2922017.png">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-camera-retro me-2"></i>LensRental
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home me-1"></i>Home
                </a>
                <a class="nav-link" href="logout.php">
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
        <?php if ($error_message): ?>
            <div class="alert alert-danger fade-in">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success fade-in">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Lens Details -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card slide-in-left">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-camera me-2"></i>Detail Lensa
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <i class="fas fa-camera fa-4x text-primary mb-3"></i>
                            <h3 class="card-title"><?php echo htmlspecialchars($lens['name']); ?></h3>
                        </div>
                        <p class="card-text"><?php echo htmlspecialchars($lens['description']); ?></p>
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="price-display mb-2"><?php echo number_format($lens['price_per_day'], 0, ',', '.'); ?></div>
                                <small class="text-muted">per hari</small>
                            </div>
                            <div class="col-6">
                                <span class="badge badge-success">
                                    <i class="fas fa-check-circle me-1"></i>Tersedia
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rental Form -->
            <div class="col-md-6">
                <div class="card slide-in-right">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>Form Penyewaan
                        </h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="rentalForm">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            
                            <div class="mb-3">
                                <label for="rental_date" class="form-label">
                                    <i class="fas fa-calendar-plus me-2"></i>Tanggal Sewa
                                </label>
                                <input type="date" class="form-control" id="rental_date" name="rental_date" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="return_date" class="form-label">
                                    <i class="fas fa-calendar-minus me-2"></i>Tanggal Kembali
                                </label>
                                <input type="date" class="form-control" id="return_date" name="return_date" required>
                            </div>
                            
                            <div class="mb-4">
                                <div class="card" style="background: var(--vintage-cream);">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">
                                            <i class="fas fa-calculator me-2"></i>Perhitungan Biaya
                                        </h6>
                                        <div id="calculation" class="text-muted">
                                            Pilih tanggal untuk melihat perhitungan
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-shopping-cart me-2"></i>Sewa Sekarang
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rental Terms -->
        <div class="row">
            <div class="col-12">
                <div class="card slide-in-left">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>Syarat dan Ketentuan
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-check-circle text-success me-2"></i>Yang Diperbolehkan:</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-arrow-right text-primary me-2"></i>Penggunaan lensa sesuai dengan petunjuk</li>
                                    <li><i class="fas fa-arrow-right text-primary me-2"></i>Pemeliharaan lensa dengan baik</li>
                                    <li><i class="fas fa-arrow-right text-primary me-2"></i>Pengembalian tepat waktu</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-times-circle text-danger me-2"></i>Yang Dilarang:</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-arrow-right text-warning me-2"></i>Menggunakan lensa di lingkungan berdebu</li>
                                    <li><i class="fas fa-arrow-right text-warning me-2"></i>Membuka atau memperbaiki lensa</li>
                                    <li><i class="fas fa-arrow-right text-warning me-2"></i>Keterlambatan pengembalian</li>
                                </ul>
                            </div>
                        </div>
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

    // Price calculation
    const rentalDate = document.getElementById('rental_date');
    const returnDate = document.getElementById('return_date');
    const calculation = document.getElementById('calculation');
    const pricePerDay = <?php echo $lens['price_per_day']; ?>;

    function calculatePrice() {
        const rental = new Date(rentalDate.value);
        const return_d = new Date(returnDate.value);
        
        if (rentalDate.value && returnDate.value && return_d > rental) {
            const days = Math.ceil((return_d - rental) / (1000 * 60 * 60 * 24));
            const total = days * pricePerDay;
            
            calculation.innerHTML = `
                <div class="row">
                    <div class="col-6">
                        <small>Durasi:</small><br>
                        <strong>${days} hari</strong>
                    </div>
                    <div class="col-6">
                        <small>Total:</small><br>
                        <strong class="price-display">${total.toLocaleString('id-ID')}</strong>
                    </div>
                </div>
            `;
        } else {
            calculation.innerHTML = 'Pilih tanggal untuk melihat perhitungan';
        }
    }

    rentalDate.addEventListener('change', calculatePrice);
    returnDate.addEventListener('change', calculatePrice);

    // Form validation
    const form = document.getElementById('rentalForm');
    form.addEventListener('submit', function(e) {
        const rental = new Date(rentalDate.value);
        const return_d = new Date(returnDate.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (rental < today) {
            e.preventDefault();
            alert('Tanggal sewa tidak boleh di masa lalu!');
            return;
        }

        if (return_d <= rental) {
            e.preventDefault();
            alert('Tanggal kembali harus setelah tanggal sewa!');
            return;
        }
    });

    // Add form focus effects
    document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
            this.parentElement.style.transition = 'transform 0.3s ease';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });
    </script>
</body>
</html>

<?php