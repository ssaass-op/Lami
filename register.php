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
$pref_seeking_gender = trim($_REQUEST['seeking_gender'] ?? $_GET['gender'] ?? '');
$pref_min_age = isset($_REQUEST['min_age']) ? intval($_REQUEST['min_age']) : 18;
$pref_max_age = isset($_REQUEST['max_age']) ? intval($_REQUEST['max_age']) : 70;
$pref_religion = trim($_REQUEST['religion'] ?? '');
$pref_location = trim($_REQUEST['location'] ?? '');
$errors = [];
$success = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security check failed. Please refresh and try again.";
    } else {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $dob = $_POST['dob'] ?? '';
        if (empty($full_name)) $errors[] = "Full Name is required.";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email address is required.";
        if (empty($phone)) $errors[] = "Phone number is required.";
        if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters long.";
        if ($password !== $confirm_password) $errors[] = "Passwords do not match.";
        if (!in_array($gender, ['male', 'female', 'other'])) $errors[] = "Please select a valid gender.";
        if (empty($dob)) {
            $errors[] = "Date of Birth is required.";
        } else {
            $age = calculate_age($dob);
            if ($age < 18) {
                $errors[] = "You must be at least 18 years old to register on this platform.";
            }
        }
        if (empty($_FILES['identity_doc']['name'])) {
            $errors[] = "An Identity Document (Citizenship, Passport, or National ID) is required for verification.";
        }
        if (empty($errors)) {
            $db = DB::getConnection();
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                $errors[] = "An account with this email address already exists.";
            }
        }
        if (empty($errors)) {
            $identity_doc_path = upload_profile_file($_FILES['identity_doc'], 'identities', ['image/jpeg', 'image/png', 'image/jpg']);
            if (!$identity_doc_path) {
                $errors[] = "Failed to upload identity document. Please ensure it is an image (JPG/PNG) under 5MB.";
            } else {
                try {
                    $db = DB::getConnection();
                    $db->beginTransaction();
                    $pass_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("
                        INSERT INTO users (full_name, email, phone, password_hash, role, status) 
                        VALUES (:name, :email, :phone, :pass, 'standard', 'pending')
                    ");
                    $stmt->execute([
                        'name' => $full_name,
                        'email' => $email,
                        'phone' => $phone,
                        'pass' => $pass_hash
                    ]);
                    $userId = (int) $db->lastInsertId();
                    $stmt = $db->prepare("
                        INSERT INTO profiles (user_id, gender, dob, identity_doc_path) 
                        VALUES (:user_id, :gender, :dob, :identity_doc_path)
                    ");
                    $stmt->execute([
                        'user_id' => $userId,
                        'gender' => $gender,
                        'dob' => $dob,
                        'identity_doc_path' => $identity_doc_path
                    ]);
                    $stmt = $db->prepare("
                        INSERT INTO privacy_settings (user_id, show_phone, show_photo, show_income) 
                        VALUES (:user_id, 0, 1, 0)
                    ");
                    $stmt->execute(['user_id' => $userId]);
                    $stmt = $db->prepare("
                        INSERT INTO matches_preferences (user_id, min_age, max_age, preferred_religion, preferred_location) 
                        VALUES (:user_id, :min_age, :max_age, :religion, :location)
                    ");
                    $stmt->execute([
                        'user_id' => $userId,
                        'min_age' => $pref_min_age > 0 ? $pref_min_age : 18,
                        'max_age' => $pref_max_age > 0 ? $pref_max_age : 70,
                        'religion' => !empty($pref_religion) ? $pref_religion : null,
                        'location' => !empty($pref_location) ? $pref_location : null
                    ]);
                    $db->commit();
                    $success = "Registration successful! Your account is pending admin verification. You can log in, but access is restricted until approved.";
                } catch (Exception $e) {
                    $db->rollBack();
                    $errors[] = "Database insertion failed: " . $e->getMessage();
                }
            }
        }
    }
}
$csrf_token = generate_csrf_token();
require_once 'includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card card-lami">
            <div class="card-header card-header-lami text-center">
                <h2 class="mb-0 fs-1">Create an Account on Lami</h2>
                <p class="text-muted small mt-2">Find your traditional partner with absolute security and dignity</p>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-lami" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo e($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-lami" role="alert">
                        <?php echo e($success); ?>
                        <div class="mt-3">
                            <a href="login.php" class="btn btn-accent-lami btn-sm">Go to Login</a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php if (!empty($pref_seeking_gender) || !empty($pref_religion) || !empty($pref_location) || $pref_min_age !== 18 || $pref_max_age !== 70): ?>
                        <div class="alert alert-info border-0 shadow-sm mb-4" style="background: #F4F7FA; border-left: 4px solid var(--accent-color) !important;">
                            <div class="d-flex align-items-center flex-wrap gap-1 mb-1">
                                <strong class="text-dark me-1">Match Preferences Saved:</strong>
                                <span class="badge bg-white text-dark border">
                                    Looking for <?php echo $pref_seeking_gender === 'female' ? 'Woman' : ($pref_seeking_gender === 'male' ? 'Man' : 'Any Gender'); ?>
                                </span>
                                <span class="badge bg-white text-dark border">
                                    Age <?php echo $pref_min_age; ?> - <?php echo $pref_max_age; ?> yrs
                                </span>
                                <?php if (!empty($pref_religion)): ?>
                                    <span class="badge bg-white text-dark border">Religion: <?php echo e($pref_religion); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($pref_location)): ?>
                                    <span class="badge bg-white text-dark border">Location: <?php echo e($pref_location); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="small text-muted">Your selected preferences will be automatically saved to your partner compatibility profile upon registration.</div>
                        </div>
                    <?php endif; ?>
                    <form action="register.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="seeking_gender" value="<?php echo e($pref_seeking_gender); ?>">
                        <input type="hidden" name="min_age" value="<?php echo e($pref_min_age); ?>">
                        <input type="hidden" name="max_age" value="<?php echo e($pref_max_age); ?>">
                        <input type="hidden" name="religion" value="<?php echo e($pref_religion); ?>">
                        <input type="hidden" name="location" value="<?php echo e($pref_location); ?>">
                        <h4 class="mb-3 border-bottom pb-2">1. Personal Information</h4>
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo e($_POST['full_name'] ?? ''); ?>" required>
                        </div>
                        <?php 
                        $selected_gender = $_POST['gender'] ?? '';
                        if (empty($selected_gender)) {
                            if ($pref_seeking_gender === 'female') $selected_gender = 'male';
                            elseif ($pref_seeking_gender === 'male') $selected_gender = 'female';
                        }
                        ?>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo $selected_gender === 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo $selected_gender === 'female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo $selected_gender === 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="dob" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="dob" name="dob" value="<?php echo e($_POST['dob'] ?? ''); ?>" required>
                                <div class="form-text fs-8">Must be 18 or older to register.</div>
                            </div>
                        </div>
                        <h4 class="mb-3 border-bottom pb-2 mt-4">2. Contact Details</h4>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" maxlength="10" minlenght="10" class="form-control" id="phone" name="phone" placeholder="98XXXXXXXX" value="<?php echo e($_POST['phone'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <h4 class="mb-3 border-bottom pb-2 mt-4">3. Security Credentials</h4>
<div class="row">
    <div class="col-md-6 mb-3">
        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
        <div class="input-group">
            <input type="password" class="form-control" id="password" name="password" required>
            <button class="btn btn-secondary" type="button" onclick="togglePasswordVisibility('password', this)">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
        <div class="input-group">
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('confirm_password', this)">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>
</div>

<script>
function togglePasswordVisibility(fieldId, btn) {
    const input = document.getElementById(fieldId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}
</script>
                       
                        <h4 class="mb-3 border-bottom pb-2 mt-4">4. Identity Verification</h4>
                        <div class="mb-4">
                            <label for="identity_doc" class="form-label">Upload Identity Document <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="identity_doc" name="identity_doc" accept="image/jpeg,image/png,image/jpg" required>
                            <div class="form-text fs-8">
                                Please upload a clear photo/scan of your Citizenship Certificate, National ID, or Passport. Accounts remain in a <strong>pending</strong> state and cannot browse matches until approved by an admin.
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary-lami py-2">Submit Registration</button>
                        </div>
                    </form>
                    <div class="text-center mt-4">
                        <p class="mb-0 text-muted small">Already have an account? <a href="login.php" class="text-accent fw-bold text-decoration-none">Login here</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
