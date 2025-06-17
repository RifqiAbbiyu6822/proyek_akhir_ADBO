<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lensa Rental - <?= isset(
        $title) ? $title : 'Home' ?></title>
    <!-- Vintage CSS Theme -->
    <link rel="stylesheet" href="<?php echo BASEURL; ?>/public/css/style.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <a href="<?php echo BASEURL; ?>/" class="navbar-brand">
                <i class="fas fa-camera"></i> Lensa Rental
            </a>
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="<?php echo BASEURL; ?>/rental" class="nav-link"><i class="fas fa-lens"></i> Rental Lensa</a></li>
                    <li><a href="<?php echo BASEURL; ?>/return" class="nav-link"><i class="fas fa-undo"></i> Kembalikan</a></li>
                    <li><a href="<?php echo BASEURL; ?>/history" class="nav-link"><i class="fas fa-history"></i> Riwayat</a></li>
                    <li><a href="<?php echo BASEURL; ?>/auth/logout" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <?php else: ?>
                    <li><a href="<?php echo BASEURL; ?>/auth/login" class="nav-link"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="<?php echo BASEURL; ?>/auth/register" class="nav-link"><i class="fas fa-user-plus"></i> Register</a></li>
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
    </main> 