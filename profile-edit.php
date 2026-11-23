<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
$user_id = $_SESSION['user_id'];
if (has_role(['admin', 'moderator'])) {
    header("Location: " . BASE_URL . "admin/dashboard.php");
    exit();
}
$errors = [];
$success = "";
$db = DB::getConnection();
try {
    $stmt = $db->prepare("SELECT full_name, phone, email FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user_data = $stmt->fetch();
    $stmt = $db->prepare("SELECT * FROM profiles WHERE user_id = :id");
    $stmt->execute(['id' => $user_id]);
    $profile_data = $stmt->fetch();
    $stmt = $db->prepare("SELECT * FROM privacy_settings WHERE user_id = :id");
    $stmt->execute(['id' => $user_id]);
    $privacy_data = $stmt->fetch();
    $stmt = $db->prepare("SELECT * FROM matches_preferences WHERE user_id = :id");
    $stmt->execute(['id' => $user_id]);
    $pref_data = $stmt->fetch();
} catch (Exception $e) {
    die("Error loading configuration: " . htmlspecialchars($e->getMessage()));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security check failed. Please refresh and try again.";
    } else {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $religion = trim($_POST['religion'] ?? '');
        $caste = trim($_POST['caste'] ?? '');
        $education = trim($_POST['education'] ?? '');
        $profession = trim($_POST['profession'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $height = !empty($_POST['height']) ? floatval($_POST['height']) : null;
        $income_range = trim($_POST['income_range'] ?? '');
        $marital_status = $_POST['marital_status'] ?? 'never_married';
        $min_age = !empty($_POST['min_age']) ? intval($_POST['min_age']) : 18;
        $max_age = !empty($_POST['max_age']) ? intval($_POST['max_age']) : 70;
        $preferred_religion = trim($_POST['preferred_religion'] ?? '');
        $preferred_caste = trim($_POST['preferred_caste'] ?? '');
        $preferred_location = trim($_POST['preferred_location'] ?? '');
        $preferred_education = trim($_POST['preferred_education'] ?? '');
        $preferred_marital_status = $_POST['preferred_marital_status'] ?? 'any';
        $preferred_profession = trim($_POST['preferred_profession'] ?? '');
        $min_height = !empty($_POST['min_height']) ? floatval($_POST['min_height']) : null;
        $max_height = !empty($_POST['max_height']) ? floatval($_POST['max_height']) : null;
        $preferred_income = trim($_POST['preferred_income'] ?? '');
        $show_phone = isset($_POST['show_phone']) ? 1 : 0;
        $show_photo = isset($_POST['show_photo']) ? 1 : 0;
        $show_income = isset($_POST['show_income']) ? 1 : 0;
        if (empty($full_name)) $errors[] = "Full Name is required.";
        if (empty($phone)) $errors[] = "Phone number is required.";
        if ($min_age < 18 || $max_age < 18) $errors[] = "Preferred age limits must be 18 or above.";
        if ($min_age > $max_age) $errors[] = "Minimum age cannot exceed Maximum age.";
        $photo_path = $profile_data['photo_path']; 
        if (!empty($_FILES['profile_photo']['name'])) {
            $uploaded_photo = upload_profile_file($_FILES['profile_photo'], 'photos', ['image/jpeg', 'image/png', 'image/jpg']);
            if ($uploaded_photo) {
                $photo_path = $uploaded_photo;
            } else {
                $errors[] = "Failed to upload photo. Ensure it is an image (JPG/PNG) under 5MB.";
            }
        }
        if (empty($errors)) {
            try {
                $db->beginTransaction();
                $stmt = $db->prepare("UPDATE users SET full_name = :name, phone = :phone WHERE id = :id");
                $stmt->execute([
                    'name' => $full_name,
                    'phone' => $phone,
                    'id' => $user_id
                ]);
                $_SESSION['user_name'] = $full_name; 
                $stmt = $db->prepare("
                    UPDATE profiles SET 
                        religion = :religion,
                        caste = :caste,
                        education = :education,
                        profession = :profession,
                        location = :location,
                        height = :height,
                        bio = :bio,
                        income_range = :income_range,
                        marital_status = :marital_status,
                        photo_path = :photo_path
                    WHERE user_id = :id
                ");
                $stmt->execute([
                    'religion' => $religion,
                    'caste' => $caste,
                    'education' => $education,
                    'profession' => $profession,
                    'location' => $location,
                    'height' => $height,
                    'bio' => $bio,
                    'income_range' => $income_range,
                    'marital_status' => $marital_status,
                    'photo_path' => $photo_path,
                    'id' => $user_id
                ]);
                $stmt = $db->prepare("
                    UPDATE privacy_settings SET 
                        show_phone = :show_phone,
                        show_photo = :show_photo,
                        show_income = :show_income
                    WHERE user_id = :id
                ");
                $stmt->execute([
                    'show_phone' => $show_phone,
                    'show_photo' => $show_photo,
                    'show_income' => $show_income,
                    'id' => $user_id
                ]);
                $stmt = $db->prepare("
                    UPDATE matches_preferences SET 
                        min_age = :min_age,
                        max_age = :max_age,
                        preferred_religion = :preferred_religion,
                        preferred_caste = :preferred_caste,
                        preferred_location = :preferred_location,
                        preferred_education = :preferred_education,
                        preferred_marital_status = :preferred_marital_status,
                        preferred_profession = :preferred_profession,
                        min_height = :min_height,
                        max_height = :max_height,
                        preferred_income = :preferred_income
                    WHERE user_id = :id
                ");
                $stmt->execute([
                    'min_age' => $min_age,
                    'max_age' => $max_age,
                    'preferred_religion' => $preferred_religion,
                    'preferred_caste' => $preferred_caste,
                    'preferred_location' => $preferred_location,
                    'preferred_education' => $preferred_education,
                    'preferred_marital_status' => $preferred_marital_status,
                    'preferred_profession' => $preferred_profession,
                    'min_height' => $min_height,
                    'max_height' => $max_height,
                    'preferred_income' => $preferred_income,
                    'id' => $user_id
                ]);
                $db->commit();
                $success = "Profile and settings updated successfully!";
                $user_data['full_name'] = $full_name;
                $user_data['phone'] = $phone;
                $profile_data['religion'] = $religion;
                $profile_data['caste'] = $caste;
                $profile_data['education'] = $education;
                $profile_data['profession'] = $profession;
                $profile_data['location'] = $location;
                $profile_data['height'] = $height;
                $profile_data['bio'] = $bio;
                $profile_data['income_range'] = $income_range;
                $profile_data['marital_status'] = $marital_status;
                $profile_data['photo_path'] = $photo_path;
                $privacy_data['show_phone'] = $show_phone;
                $privacy_data['show_photo'] = $show_photo;
                $privacy_data['show_income'] = $show_income;
                $pref_data['min_age'] = $min_age;
                $pref_data['max_age'] = $max_age;
                $pref_data['preferred_religion'] = $preferred_religion;
                $pref_data['preferred_caste'] = $preferred_caste;
                $pref_data['preferred_location'] = $preferred_location;
                $pref_data['preferred_education'] = $preferred_education;
                $pref_data['preferred_marital_status'] = $preferred_marital_status;
                $pref_data['preferred_profession'] = $preferred_profession;
                $pref_data['min_height'] = $min_height;
                $pref_data['max_height'] = $max_height;
                $pref_data['preferred_income'] = $preferred_income;
            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = "Failed to save configurations: " . $e->getMessage();
            }
        }
    }
}
$csrf_token = generate_csrf_token();
require_once 'includes/header.php';
?>
<div class="row">
    <div class="col-lg-3 mb-4">
        <div class="card card-lami text-center p-4 mb-4">
            <div class="profile-pic-container mb-3">
                <?php if (!empty($profile_data['photo_path']) && file_exists($profile_data['photo_path'])): ?>
                    <img src="<?php echo e($profile_data['photo_path']); ?>" alt="Profile Picture">
                <?php else: ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-secondary">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                <?php endif; ?>
            </div>
            <h4 class="mb-1 fs-3"><?php echo e($user_data['full_name']); ?></h4>
            <p class="text-muted small mb-0"><?php echo e(ucfirst($profile_data['gender'] ?? '')); ?> | <?php echo e(calculate_age($profile_data['dob'])); ?> Yrs</p>
            <p class="text-muted small mb-3"><?php echo e($user_data['email']); ?></p>
            <a href="profile.php?id=<?php echo $user_id; ?>" class="btn btn-outline-lami btn-sm w-100">View Public Profile</a>
        </div>
        <div class="card card-lami p-2">
            <div class="nav flex-column nav-pills" id="settingsTabs" role="tablist" aria-orientation="vertical">
                <button class="nav-link nav-link-lami active text-start py-2.5 px-3" id="personal-tab" data-bs-toggle="pill" data-bs-target="#personal" type="button" role="tab" aria-controls="personal" aria-selected="true">Personal Details</button>
                <button class="nav-link nav-link-lami text-start py-2.5 px-3" id="socio-tab" data-bs-toggle="pill" data-bs-target="#socio" type="button" role="tab" aria-controls="socio" aria-selected="false">Socio-Professional</button>
                <button class="nav-link nav-link-lami text-start py-2.5 px-3" id="partner-tab" data-bs-toggle="pill" data-bs-target="#partner" type="button" role="tab" aria-controls="partner" aria-selected="false">Partner Preferences</button>
                <button class="nav-link nav-link-lami text-start py-2.5 px-3" id="privacy-tab" data-bs-toggle="pill" data-bs-target="#privacy" type="button" role="tab" aria-controls="privacy" aria-selected="false">Privacy Controls</button>
            </div>
        </div>
    </div>
    <div class="col-lg-9">
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
            </div>
        <?php endif; ?>
        <form action="profile-edit.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="tab-content" id="settingsTabsContent">
                <div class="tab-pane fade show active" id="personal" role="tabpanel" aria-labelledby="personal-tab">
                    <div class="card card-lami p-4">
                        <h3 class="border-bottom pb-2 mb-4 fs-2">Personal Details</h3>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo e($user_data['full_name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo e($user_data['phone']); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="height" class="form-label">Height (in cm)</label>
                                <input type="number" step="0.1" class="form-control" id="height" name="height" placeholder="e.g. 175.5" value="<?php echo e($profile_data['height']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="marital_status" class="form-label">Marital Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="marital_status" name="marital_status" required>
                                    <option value="never_married" <?php echo ($profile_data['marital_status'] ?? '') === 'never_married' ? 'selected' : ''; ?>>Never Married</option>
                                    <option value="divorced" <?php echo ($profile_data['marital_status'] ?? '') === 'divorced' ? 'selected' : ''; ?>>Divorced</option>
                                    <option value="widowed" <?php echo ($profile_data['marital_status'] ?? '') === 'widowed' ? 'selected' : ''; ?>>Widowed</option>
                                    <option value="awaited" <?php echo ($profile_data['marital_status'] ?? '') === 'awaited' ? 'selected' : ''; ?>>Awaited</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="bio" class="form-label">About Me (Bio)</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4" placeholder="Describe yourself, your hobbies, interests, and what you are looking for..." required><?php echo e($profile_data['bio']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="profile_photo" class="form-label">Change Profile Photo</label>
                            <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/jpeg,image/png,image/jpg">
                            <div class="form-text fs-8">Uploading a new photo replaces the existing one. Recommended size: under 5MB.</div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary-lami">Save Settings</button>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="socio" role="tabpanel" aria-labelledby="socio-tab">
                    <div class="card card-lami p-4">
                        <h3 class="border-bottom pb-2 mb-4 fs-2">Socio-Professional Profile</h3>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="religion" class="form-label">Religion</label>
                                <select class="form-select" id="religion" name="religion">
                                    <option value="">Select Religion</option>
                                    <?php 
                                    $religions = ['Hindu', 'Buddhist', 'Kirat', 'Christian', 'Islam', 'Sikh', 'Other'];
                                    foreach ($religions as $rel):
                                    ?>
                                        <option value="<?php echo $rel; ?>" <?php echo ($profile_data['religion'] ?? '') === $rel ? 'selected' : ''; ?>><?php echo $rel; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="caste" class="form-label">Caste / Ethnic Group</label>
                                <input type="text" class="form-control" id="caste" name="caste" placeholder="e.g. Brahmin, Newar, Chhetri" value="<?php echo e($profile_data['caste']); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="education" class="form-label">Education Qualification</label>
                                <input type="text" class="form-control" id="education" name="education" placeholder="e.g. Bachelor in Software Engineering" value="<?php echo e($profile_data['education']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="profession" class="form-label">Profession / Job</label>
                                <input type="text" class="form-control" id="profession" name="profession" placeholder="e.g. Bank Manager, Teacher" value="<?php echo e($profile_data['profession']); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="location" class="form-label">Current Location (City/District)</label>
                                <input type="text" class="form-control" id="location" name="location" placeholder="e.g. Kathmandu, Pokhara" value="<?php echo e($profile_data['location']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="income_range" class="form-label">Annual Income Range</label>
                                <select class="form-select" id="income_range" name="income_range">
                                    <option value="">Select Income Range</option>
                                    <?php 
                                    $incomes = ['Under Rs. 3 Lakhs', 'Rs. 3 Lakhs - 5 Lakhs', 'Rs. 5 Lakhs - 10 Lakhs', 'Rs. 10 Lakhs - 15 Lakhs', 'Rs. 15 Lakhs - 25 Lakhs', 'Rs. 25 Lakhs+', 'Prefer not to say'];
                                    foreach ($incomes as $inc):
                                    ?>
                                        <option value="<?php echo $inc; ?>" <?php echo ($profile_data['income_range'] ?? '') === $inc ? 'selected' : ''; ?>><?php echo $inc; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary-lami">Save Settings</button>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="partner" role="tabpanel" aria-labelledby="partner-tab">
                    <div class="card card-lami p-4">
                        <h3 class="border-bottom pb-2 mb-4 fs-2">Partner Preferences</h3>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="min_age" class="form-label">Minimum Age</label>
                                <input type="number" class="form-control" id="min_age" name="min_age" min="18" max="70" value="<?php echo e($pref_data['min_age'] ?? 18); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="max_age" class="form-label">Maximum Age</label>
                                <input type="number" class="form-control" id="max_age" name="max_age" min="18" max="70" value="<?php echo e($pref_data['max_age'] ?? 70); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="preferred_marital_status" class="form-label">Preferred Marital Status</label>
                                <select class="form-select" id="preferred_marital_status" name="preferred_marital_status">
                                    <option value="any" <?php echo ($pref_data['preferred_marital_status'] ?? 'any') === 'any' ? 'selected' : ''; ?>>Any Status</option>
                                    <option value="never_married" <?php echo ($pref_data['preferred_marital_status'] ?? '') === 'never_married' ? 'selected' : ''; ?>>Never Married</option>
                                    <option value="divorced" <?php echo ($pref_data['preferred_marital_status'] ?? '') === 'divorced' ? 'selected' : ''; ?>>Divorced</option>
                                    <option value="widowed" <?php echo ($pref_data['preferred_marital_status'] ?? '') === 'widowed' ? 'selected' : ''; ?>>Widowed</option>
                                    <option value="awaited" <?php echo ($pref_data['preferred_marital_status'] ?? '') === 'awaited' ? 'selected' : ''; ?>>Awaited</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="min_height" class="form-label">Min Height (cm)</label>
                                <input type="number" step="0.1" class="form-control" id="min_height" name="min_height" placeholder="e.g. 150.0" value="<?php echo e($pref_data['min_height'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="max_height" class="form-label">Max Height (cm)</label>
                                <input type="number" step="0.1" class="form-control" id="max_height" name="max_height" placeholder="e.g. 185.0" value="<?php echo e($pref_data['max_height'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="preferred_religion" class="form-label">Preferred Religion</label>
                                <select class="form-select" id="preferred_religion" name="preferred_religion">
                                    <option value="">No Preference</option>
                                    <?php foreach ($religions as $rel): ?>
                                        <option value="<?php echo $rel; ?>" <?php echo ($pref_data['preferred_religion'] ?? '') === $rel ? 'selected' : ''; ?>><?php echo $rel; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="preferred_caste" class="form-label">Preferred Caste / Ethnic Group</label>
                                <input type="text" class="form-control" id="preferred_caste" name="preferred_caste" placeholder="e.g. Brahmin, Chhetri" value="<?php echo e($pref_data['preferred_caste'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="preferred_location" class="form-label">Preferred Location</label>
                                <input type="text" class="form-control" id="preferred_location" name="preferred_location" placeholder="e.g. Kathmandu" value="<?php echo e($pref_data['preferred_location'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="preferred_education" class="form-label">Preferred Education Level</label>
                                <input type="text" class="form-control" id="preferred_education" name="preferred_education" placeholder="e.g. Bachelor, Master" value="<?php echo e($pref_data['preferred_education'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="preferred_profession" class="form-label">Preferred Profession</label>
                                <input type="text" class="form-control" id="preferred_profession" name="preferred_profession" placeholder="e.g. Software Engineer, Doctor" value="<?php echo e($pref_data['preferred_profession'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="preferred_income" class="form-label">Preferred Income Range</label>
                                <select class="form-select" id="preferred_income" name="preferred_income">
                                    <option value="">No Preference</option>
                                    <?php foreach ($incomes as $inc): ?>
                                        <option value="<?php echo $inc; ?>" <?php echo ($pref_data['preferred_income'] ?? '') === $inc ? 'selected' : ''; ?>><?php echo $inc; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary-lami">Save Settings</button>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="privacy" role="tabpanel" aria-labelledby="privacy-tab">
                    <div class="card card-lami p-4">
                        <h3 class="border-bottom pb-2 mb-4 fs-2">Granular Privacy Controls</h3>
                        <p class="text-muted small mb-4">
                            Define who can view your sensitive information. These rules apply to standard users browsing the platform. (Administrators and moderators always see full profiles to enforce security guidelines).
                        </p>
                        <div class="mb-4 form-check form-switch p-3 border-bottom">
                            <input class="form-check-input ms-0 me-3" type="checkbox" role="switch" id="show_photo" name="show_photo" <?php echo ($privacy_data['show_photo'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-bold" for="show_photo">Show Profile Photo to Public</label>
                            <div class="form-text fs-8 text-secondary">
                                If turned OFF, non-accepted matches will see a generic gender-specific SVG avatar. Your actual photo is only shown when you <strong>accept</strong> their connection request.
                            </div>
                        </div>
                        <div class="mb-4 form-check form-switch p-3 border-bottom">
                            <input class="form-check-input ms-0 me-3" type="checkbox" role="switch" id="show_phone" name="show_phone" <?php echo ($privacy_data['show_phone'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-bold" for="show_phone">Show Phone Number to Connections</label>
                            <div class="form-text fs-8 text-secondary">
                                If turned ON, your phone number is shared <strong>only</strong> with matches whose connection requests you have accepted. It remains hidden from general search queries.
                            </div>
                        </div>
                        <div class="mb-4 form-check form-switch p-3 border-bottom">
                            <input class="form-check-input ms-0 me-3" type="checkbox" role="switch" id="show_income" name="show_income" <?php echo ($privacy_data['show_income'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-bold" for="show_income">Show Annual Income to Connections</label>
                            <div class="form-text fs-8 text-secondary">
                                If turned ON, your annual income range is visible <strong>only</strong> to approved connections. If OFF, it will show as "Undisclosed" or "Hidden by User".
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary-lami">Save Settings</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
