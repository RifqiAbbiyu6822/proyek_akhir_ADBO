<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lensa Rental - <?= $title ?? 'Home' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <a href="/" class="navbar-brand">
                <i class="fas fa-camera"></i> Lensa Rental
            </a>
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="/rental" class="nav-link"><i class="fas fa-lens"></i> Rental Lensa</a></li>
                    <li><a href="/return" class="nav-link"><i class="fas fa-undo"></i> Kembalikan</a></li>
                    <li><a href="/history" class="nav-link"><i class="fas fa-history"></i> Riwayat</a></li>
                    <li><a href="/auth/logout" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <?php else: ?>
                    <li><a href="/auth/login" class="nav-link"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="/auth/register" class="nav-link"><i class="fas fa-user-plus"></i> Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main class="container">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?>">
                <?= $_SESSION['flash_message'] ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?> 