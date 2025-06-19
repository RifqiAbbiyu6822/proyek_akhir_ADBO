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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="https://cdn-icons-png.flaticon.com/512/2922/2922017.png">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --accent: #38bdf8;
            --bg: #f8fafc;
            --card-bg: #fff;
            --text: #222;
            --border: #e5e7eb;
            --shadow: 0 2px 16px rgba(0,0,0,0.06);
        }
        [data-theme="dark"] {
            --primary: #60a5fa;
            --primary-dark: #2563eb;
            --accent: #38bdf8;
            --bg: #181a20;
            --card-bg: #23262f;
            --text: #f3f4f6;
            --border: #2d2f36;
            --shadow: 0 2px 16px rgba(0,0,0,0.18);
        }
        html, body {
            font-family: 'Inter', Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1030;
            background: var(--primary-dark) !important;
            box-shadow: var(--shadow);
        }
        .navbar .nav-link {
            color: #fff !important;
            font-weight: 500;
            transition: color 0.2s;
        }
        .navbar .nav-link:hover {
            color: var(--accent) !important;
        }
        .theme-toggle {
            background: none;
            border: none;
            color: #fff;
            font-size: 1.3em;
            margin-left: 1rem;
            cursor: pointer;
            transition: color 0.2s;
        }
        .theme-toggle:hover {
            color: var(--accent);
        }
        .hero-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            padding: 100px 0;
        }
        .lens-card {
            transition: transform 0.3s, box-shadow 0.2s;
            border-radius: 16px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }
        .lens-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 4px 24px rgba(37,99,235,0.08);
        }
        .card {
            background: var(--card-bg);
            box-shadow: var(--shadow);
            border-radius: 16px;
            border: 1px solid var(--border);
        }
        .btn {
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.2px;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
        }
        .btn-primary {
            background: var(--primary);
            border-color: var(--primary-dark);
        }
        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary);
        }
        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
        }
        .btn-outline-primary:hover {
            background: var(--primary);
            color: #fff;
        }
        @media (max-width: 576px) {
            .card, .lens-card {
                border-radius: 10px;
            }
            .main-content {
                padding: 0 0.5rem;
            }
            .footer {
                padding: 10px 0 6px 0;
            }
        }
        .footer {
            position: fixed;
            left: 0;
            bottom: 0;
            width: 100%;
            background: var(--card-bg);
            color: var(--text);
            text-align: center;
            padding: 16px 0 8px 0;
            border-top: 1px solid var(--border);
            box-shadow: var(--shadow);
        }
        body {
            padding-bottom: 60px; /* Height of footer */
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
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == 4): ?>
                        <a class="nav-link" href="admin/index.php">Admin Panel</a>
                    <?php endif; ?>
                    <a class="nav-link" href="logout.php">Logout</a>
                <?php else: ?>
                    <a class="nav-link" href="login.php">Login</a>
                    <a class="nav-link" href="register.php">Register</a>
                <?php endif; ?>
                <button class="theme-toggle" id="themeToggle" title="Toggle dark mode"><i class="fa fa-moon"></i></button>
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

    <footer class="footer mt-auto">
        <div class="container">
            <span>&copy; <?php echo date('Y'); ?> LensRental. All rights reserved.</span>
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
    </script>
</body>
</html>
