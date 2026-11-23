<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
if (is_logged_in()) {
    if (($_SESSION['user_status'] ?? '') === 'verified') {
        if (in_array($_SESSION['user_role'] ?? 'standard', ['admin', 'moderator'])) {
            header("Location: " . BASE_URL . "admin/dashboard.php");
        } else {
            header("Location: " . BASE_URL . "index.php");
        }
    } else {
        header("Location: " . BASE_URL . "pending-status.php");
    }
    exit();
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security check failed. Please refresh and try again.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (empty($email) || empty($password)) {
            $errors[] = "Please fill in all fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        } else {
            $db = DB::getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['status'] === 'suspended') {
                    $errors[] = "Your account has been suspended by the administrator.";
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int) $user['id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_status'] = $user['status'];
                    if ($user['status'] === 'verified') {
                        if (in_array($user['role'], ['admin', 'moderator'])) {
                            header("Location: " . BASE_URL . "admin/dashboard.php");
                        } else {
                            header("Location: " . BASE_URL . "index.php");
                        }
                    } else {
                        header("Location: " . BASE_URL . "pending-status.php");
                    }
                    exit();
                }
            } else {
                $errors[] = "Invalid email or password.";
            }
        }
    }
}
$csrf_token = generate_csrf_token();
require_once 'includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card card-lami">
            <div class="card-header card-header-lami text-center">
                <h2 class="mb-0 fs-1">Login to Lami</h2>
                <p class="text-muted small mt-2">Resume your search for a compatible life partner</p>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-lami text-center" role="alert">
                        <?php echo e($errors[0]); ?>
                    </div>
                <?php endif; ?>
                <form action="login.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary-lami py-2">Secure Login</button>
                    </div>
                </form>
                <div class="text-center mt-4">
                    <p class="mb-0 text-muted small">Don't have an account yet? <a href="register.php" class="text-accent fw-bold text-decoration-none">Register here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
