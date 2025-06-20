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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="includes/vintage-theme.css">
    <link rel="icon" type="image/png" href="https://cdn-icons-png.flaticon.com/512/2922/2922017.png">
</head>
<body>
    <?php
    $database = new Database();
    $db = $database->getConnection();
    $lens = new Lens($db);
    $stmt = $lens->readAvailable();
    ?>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-camera-retro me-2"></i>LensRental
            </a>
            <div class="navbar-nav ms-auto">
                <?php if (isLoggedIn()): ?>
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == 4): ?>
                        <a class="nav-link" href="admin/index.php">
                            <i class="fas fa-cog me-1"></i>Admin Panel
                        </a>
                    <?php endif; ?>
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                <?php else: ?>
                    <a class="nav-link" href="login.php">
                        <i class="fas fa-sign-in-alt me-1"></i>Login
                    </a>
                    <a class="nav-link" href="register.php">
                        <i class="fas fa-user-plus me-1"></i>Register
                    </a>
                <?php endif; ?>
                <button class="theme-toggle" id="themeToggle" title="Toggle dark mode">
                    <i class="fa fa-moon"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center fade-in">
        <div class="container">
            <h1 class="display-4 mb-4">
                <i class="fas fa-camera-retro me-3"></i>
                Sewa Lensa Kamera Profesional
            </h1>
            <p class="lead mb-4">Temukan lensa terbaik untuk kebutuhan fotografi Anda dengan kualitas premium dan harga terjangkau</p>
            <?php if (!isLoggedIn()): ?>
                <a href="register.php" class="btn btn-light btn-lg">
                    <i class="fas fa-rocket me-2"></i>Mulai Sekarang
                </a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Available Lenses -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5 slide-in-left">
                <h2 class="display-5 mb-3">
                    <i class="fas fa-lens me-3"></i>Lensa Tersedia
                </h2>
                <p class="lead text-muted">Pilihan lensa berkualitas untuk berbagai kebutuhan fotografi</p>
            </div>
            <div class="row">
                <?php 
                $counter = 0;
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): 
                    $counter++;
                    $animationClass = $counter % 2 == 0 ? 'slide-in-right' : 'slide-in-left';
                ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card lens-card h-100 <?php echo $animationClass; ?>">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-camera fa-3x text-muted"></i>
                            </div>
                            <h5 class="card-title"><?php echo htmlspecialchars($row['name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($row['description']); ?></p>
                            <div class="price-display mb-3">
                                <?php echo number_format($row['price_per_day'], 0, ',', '.'); ?>/hari
                            </div>
                            <?php if (isLoggedIn()): ?>
                                <a href="rent.php?id=<?php echo $row['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-shopping-cart me-2"></i>Sewa Sekarang
                                </a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-outline-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login untuk Menyewa
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5" style="background: var(--vintage-cream);">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 mb-3">Mengapa Memilih Kami?</h2>
                <p class="lead text-muted">Layanan terbaik untuk kebutuhan fotografi Anda</p>
            </div>
            <div class="row">
                <div class="col-md-4 mb-4 text-center">
                    <div class="card h-100 border-0" style="background: transparent;">
                        <div class="card-body">
                            <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">Kualitas Terjamin</h5>
                            <p class="card-text">Semua lensa kami dijamin berkualitas tinggi dan terawat dengan baik.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4 text-center">
                    <div class="card h-100 border-0" style="background: transparent;">
                        <div class="card-body">
                            <i class="fas fa-clock fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">Layanan 24/7</h5>
                            <p class="card-text">Sistem online yang memungkinkan Anda menyewa kapan saja dan di mana saja.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4 text-center">
                    <div class="card h-100 border-0" style="background: transparent;">
                        <div class="card-body">
                            <i class="fas fa-dollar-sign fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">Harga Terjangkau</h5>
                            <p class="card-text">Harga sewa yang kompetitif dengan kualitas yang tidak diragukan.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

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

    // Add scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe all cards for animation
    document.querySelectorAll('.lens-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
    </script>
</body>
</html>
