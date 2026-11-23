<?php
require_once __DIR__ . '/functions.php';
$base_path = '';
if (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) {
    $base_path = '../';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lami - Nepalese Matrimonial Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo $base_path; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-lami sticky-top">
    <div class="container">
        <a class="navbar-brand navbar-brand-lami" href="<?php echo $base_path; ?>index.php">
            Lami<span>.</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if (is_logged_in() && ($_SESSION['user_status'] ?? 'pending') === 'verified' && !has_role(['admin', 'moderator'])): ?>
                    <li class="nav-item">
                        <a class="nav-link nav-link-lami <?php echo basename($_SERVER['PHP_SELF']) === 'matches.php' ? 'active' : ''; ?>" href="<?php echo $base_path; ?>matches.php">Matches</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-lami <?php echo basename($_SERVER['PHP_SELF']) === 'search.php' ? 'active' : ''; ?>" href="<?php echo $base_path; ?>search.php">Search</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-lami <?php echo basename($_SERVER['PHP_SELF']) === 'connect.php' ? 'active' : ''; ?>" href="<?php echo $base_path; ?>connect.php">Connections</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-lami <?php echo basename($_SERVER['PHP_SELF']) === 'messages.php' ? 'active' : ''; ?>" href="<?php echo $base_path; ?>messages.php">Messages</a>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                <?php if (is_logged_in()): ?>
                    <?php if (has_role(['admin', 'moderator'])): ?>
                        <li class="nav-item me-3">
                            <a class="btn btn-outline-lami py-1 px-3 fs-6" href="<?php echo $base_path; ?>admin/dashboard.php">Admin Panel</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle nav-link-lami" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Hi, <?php echo e($_SESSION['user_name'] ?? 'User'); ?> 
                            <span class="badge bg-secondary ms-1 fs-8"><?php echo e(ucfirst($_SESSION['user_status'] ?? 'pending')); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userDropdown">
                            <?php if (($_SESSION['user_status'] ?? '') === 'verified' && !has_role(['admin', 'moderator'])): ?>
                                <li><a class="dropdown-menu-item dropdown-item" href="<?php echo $base_path; ?>profile.php?id=<?php echo $_SESSION['user_id']; ?>">My Profile</a></li>
                                <li><a class="dropdown-menu-item dropdown-item" href="<?php echo $base_path; ?>profile-edit.php">Edit Profile & Privacy</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item text-danger" href="<?php echo $base_path; ?>logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item me-2">
                        <a class="btn btn-outline-lami px-3 py-1.5" href="<?php echo $base_path; ?>login.php">Log In</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary-lami px-3 py-1.5" href="<?php echo $base_path; ?>register.php">Register Free</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="container my-4">
