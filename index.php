<?php
require_once 'config/database.php';
require_once 'classes/Lens.php';
require_once 'includes/auth.php';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lens Rental - Sewa Lensa Kamera</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
        }
        .lens-card {
            transition: transform 0.3s;
        }
        .lens-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <?php
    $database = new Database();
    $db = $database->getConnection();
    $lens = new Lens($db);
    $stmt = $lens->readAvailable();
    ?>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">LensRental</a>
            <div class="navbar-nav ms-auto">
                <?php if (isLoggedIn()): ?>
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                    <a class="nav-link" href="logout.php">Logout</a>
                <?php else: ?>
                    <a class="nav-link" href="login.php">Login</a>
                    <a class="nav-link" href="register.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 mb-4">Sewa Lensa Kamera Profesional</h1>
            <p class="lead mb-4">Temukan lensa terbaik untuk kebutuhan fotografi Anda</p>
            <?php if (!isLoggedIn()): ?>
                <a href="register.php" class="btn btn-light btn-lg">Mulai Sekarang</a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Available Lenses -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Lensa Tersedia</h2>
            <div class="row">
                <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card lens-card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($row['name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($row['description']); ?></p>
                            <p class="card-text">
                                <strong>Rp <?php echo number_format($row['price_per_day'], 0, ',', '.'); ?>/hari</strong>
                            </p>
                            <?php if (isLoggedIn()): ?>
                                <a href="rent.php?id=<?php echo $row['id']; ?>" class="btn btn-primary">Sewa Sekarang</a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-outline-primary">Login untuk Menyewa</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
