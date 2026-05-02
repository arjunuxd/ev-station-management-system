<?php
require_once __DIR__ . '/../includes/auth.php';
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : '' ?>EV Station</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg ev-navbar">
    <div class="container-fluid px-4">
        <a class="navbar-brand ev-brand" href="<?= BASE_URL ?>/<?= isAdmin() ? 'admin' : 'user' ?>/index.php">
            <span class="brand-icon"><i class="fas fa-bolt"></i></span>
            <span class="brand-text">EV<span class="brand-accent">Station</span></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto ms-4">
                <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'index.php' && $currentDir === 'admin' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/index.php">
                            <i class="fas fa-gauge-high me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'stations.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/stations.php">
                            <i class="fas fa-charging-station me-1"></i> Stations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'connectors.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/connectors.php">
                            <i class="fas fa-plug me-1"></i> Connectors
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'bookings.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/bookings.php">
                            <i class="fas fa-calendar-check me-1"></i> Bookings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'users.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/users.php">
                            <i class="fas fa-users me-1"></i> Users
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'index.php' && $currentDir === 'user' ? 'active' : '' ?>" href="<?= BASE_URL ?>/user/index.php">
                            <i class="fas fa-charging-station me-1"></i> Stations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'my_bookings.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/user/my_bookings.php">
                            <i class="fas fa-calendar-check me-1"></i> My Bookings
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            <div class="navbar-nav ms-auto align-items-center gap-3">
                <?php if ($currentUser): ?>
                    <div class="nav-user-info">
                        <span class="user-avatar"><i class="fas fa-user"></i></span>
                        <span class="user-name"><?= htmlspecialchars($currentUser['name']) ?></span>
                        <?php if (isAdmin()): ?>
                            <span class="badge-role admin">Admin</span>
                        <?php else: ?>
                            <span class="badge-role user">User</span>
                        <?php endif; ?>
                    </div>
                    <a class="btn btn-logout" href="<?= BASE_URL ?>/logout.php">
                        <i class="fas fa-right-from-bracket me-1"></i> Logout
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<div class="page-wrapper">