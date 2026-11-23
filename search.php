<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
$current_user = $_SESSION['user_id'];
$db = DB::getConnection();
$stmt = $db->prepare("SELECT gender FROM profiles WHERE user_id = :id");
$stmt->execute(['id' => $current_user]);
$my_profile = $stmt->fetch();
$my_gender = $my_profile ? $my_profile['gender'] : 'male';
$default_search_gender = ($my_gender === 'male') ? 'female' : 'male';
$search_gender = $_GET['gender'] ?? $default_search_gender;
$min_age = isset($_GET['min_age']) ? intval($_GET['min_age']) : 18;
$max_age = isset($_GET['max_age']) ? intval($_GET['max_age']) : 50;
$religion = trim($_GET['religion'] ?? '');
$caste = trim($_GET['caste'] ?? '');
$location = trim($_GET['location'] ?? '');
$education = trim($_GET['education'] ?? '');
$profession = trim($_GET['profession'] ?? '');
$marital_status = trim($_GET['marital_status'] ?? '');
$min_height = !empty($_GET['min_height']) ? floatval($_GET['min_height']) : null;
$max_height = !empty($_GET['max_height']) ? floatval($_GET['max_height']) : null;
$income_range = trim($_GET['income_range'] ?? '');
$query_parts = [];
$params = ['my_id' => $current_user];
$sql = "
    SELECT u.id, u.full_name, 
           p.gender, p.dob, p.religion, p.caste, p.education, p.profession, p.location, p.height, p.photo_path, p.marital_status, p.income_range,
           ps.show_photo
    FROM users u
    JOIN profiles p ON u.id = p.user_id
    JOIN privacy_settings ps ON u.id = ps.user_id
    WHERE u.status = 'verified' AND u.id != :my_id
";
if (!empty($search_gender)) {
    $query_parts[] = "p.gender = :gender";
    $params['gender'] = $search_gender;
}
if ($min_age > 0) {
    $query_parts[] = "(YEAR(CURDATE()) - YEAR(p.dob)) >= :min_age";
    $params['min_age'] = $min_age;
}
if ($max_age > 0) {
    $query_parts[] = "(YEAR(CURDATE()) - YEAR(p.dob)) <= :max_age";
    $params['max_age'] = $max_age;
}
if (!empty($religion)) {
    $query_parts[] = "p.religion = :religion";
    $params['religion'] = $religion;
}
if (!empty($caste)) {
    $query_parts[] = "p.caste LIKE :caste";
    $params['caste'] = "%$caste%";
}
if (!empty($location)) {
    $query_parts[] = "p.location LIKE :location";
    $params['location'] = "%$location%";
}
if (!empty($education)) {
    $query_parts[] = "p.education LIKE :education";
    $params['education'] = "%$education%";
}
if (!empty($profession)) {
    $query_parts[] = "p.profession LIKE :profession";
    $params['profession'] = "%$profession%";
}
if (!empty($marital_status)) {
    $query_parts[] = "p.marital_status = :marital_status";
    $params['marital_status'] = $marital_status;
}
if (!empty($min_height)) {
    $query_parts[] = "p.height >= :min_height";
    $params['min_height'] = $min_height;
}
if (!empty($max_height)) {
    $query_parts[] = "p.height <= :max_height";
    $params['max_height'] = $max_height;
}
if (!empty($income_range)) {
    $query_parts[] = "p.income_range = :income_range";
    $params['income_range'] = $income_range;
}
if (!empty($query_parts)) {
    $sql .= " AND " . implode(" AND ", $query_parts);
}
$sql .= " ORDER BY u.created_at DESC";
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
} catch (Exception $e) {
    die("Search execution error: " . htmlspecialchars($e->getMessage()));
}
require_once 'includes/header.php';
?>
<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card card-lami p-4">
            <h3 class="mb-4 fs-2 border-bottom pb-2">Filter Profiles</h3>
            <form action="search.php" method="GET">
                <div class="mb-3">
                    <label for="gender" class="form-label">Seeking Gender</label>
                    <select class="form-select" id="gender" name="gender">
                        <option value="">Any</option>
                        <option value="male" <?php echo $search_gender === 'male' ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo $search_gender === 'female' ? 'selected' : ''; ?>>Female</option>
                        <option value="other" <?php echo $search_gender === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label for="min_age" class="form-label">Min Age</label>
                        <input type="number" class="form-control" id="min_age" name="min_age" min="18" max="70" value="<?php echo $min_age; ?>">
                    </div>
                    <div class="col-6 mb-3">
                        <label for="max_age" class="form-label">Max Age</label>
                        <input type="number" class="form-control" id="max_age" name="max_age" min="18" max="70" value="<?php echo $max_age; ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="marital_status" class="form-label">Marital Status</label>
                    <select class="form-select" id="marital_status" name="marital_status">
                        <option value="">Any Marital Status</option>
                        <option value="never_married" <?php echo $marital_status === 'never_married' ? 'selected' : ''; ?>>Never Married</option>
                        <option value="divorced" <?php echo $marital_status === 'divorced' ? 'selected' : ''; ?>>Divorced</option>
                        <option value="widowed" <?php echo $marital_status === 'widowed' ? 'selected' : ''; ?>>Widowed</option>
                        <option value="awaited" <?php echo $marital_status === 'awaited' ? 'selected' : ''; ?>>Awaited</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label for="min_height" class="form-label">Min Height (cm)</label>
                        <input type="number" step="0.1" class="form-control" id="min_height" name="min_height" placeholder="e.g. 150" value="<?php echo e($min_height ?? ''); ?>">
                    </div>
                    <div class="col-6 mb-3">
                        <label for="max_height" class="form-label">Max Height (cm)</label>
                        <input type="number" step="0.1" class="form-control" id="max_height" name="max_height" placeholder="e.g. 185" value="<?php echo e($max_height ?? ''); ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="religion" class="form-label">Religion</label>
                    <select class="form-select" id="religion" name="religion">
                        <option value="">Any Religion</option>
                        <?php 
                        $religions = ['Hindu', 'Buddhist', 'Kirat', 'Christian', 'Islam', 'Sikh', 'Other'];
                        foreach ($religions as $rel):
                        ?>
                            <option value="<?php echo $rel; ?>" <?php echo $religion === $rel ? 'selected' : ''; ?>><?php echo $rel; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="caste" class="form-label">Caste / Ethnic Group</label>
                    <input type="text" class="form-control" id="caste" name="caste" placeholder="e.g. Brahmin, Newar" value="<?php echo e($caste); ?>">
                </div>
                <div class="mb-3">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" class="form-control" id="location" name="location" placeholder="e.g. Kathmandu, Lalitpur" value="<?php echo e($location); ?>">
                </div>
                <div class="mb-3">
                    <label for="education" class="form-label">Education</label>
                    <input type="text" class="form-control" id="education" name="education" placeholder="e.g. Bachelor, Master" value="<?php echo e($education); ?>">
                </div>
                <div class="mb-3">
                    <label for="profession" class="form-label">Profession</label>
                    <input type="text" class="form-control" id="profession" name="profession" placeholder="e.g. Engineer, Doctor" value="<?php echo e($profession); ?>">
                </div>
                <div class="mb-3">
                    <label for="income_range" class="form-label">Income Range</label>
                    <select class="form-select" id="income_range" name="income_range">
                        <option value="">Any Income</option>
                        <?php 
                        $incomes = ['Under Rs. 3 Lakhs', 'Rs. 3 Lakhs - 5 Lakhs', 'Rs. 5 Lakhs - 10 Lakhs', 'Rs. 10 Lakhs - 15 Lakhs', 'Rs. 15 Lakhs - 25 Lakhs', 'Rs. 25 Lakhs+'];
                        foreach ($incomes as $inc):
                        ?>
                            <option value="<?php echo $inc; ?>" <?php echo $income_range === $inc ? 'selected' : ''; ?>><?php echo $inc; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary-lami py-2">Apply Filters</button>
                    <a href="search.php" class="btn btn-outline-lami mt-2 py-2">Clear Filters</a>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0 fs-2 text-dark">Search Results (<?php echo count($results); ?>)</h2>
        </div>
        <?php if (empty($results)): ?>
            <div class="card card-lami p-5 text-center">
                <svg width="60" height="60" class="mx-auto text-muted mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
                <h4>No Profiles Matched</h4>
                <p class="text-muted small">Try broadening your search fields or adjusting the age range.</p>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 g-4">
                <?php foreach ($results as $profile): ?>
                    <?php 
                    $is_connected = is_connected($current_user, $profile['id']);
                    $show_img = ($profile['show_photo'] || $is_connected);
                    ?>
                    <div class="col">
                        <div class="card card-lami h-100 d-flex flex-column justify-content-between p-3">
                            <div class="text-center mb-3">
                                <div class="profile-pic-container mb-3" style="width: 100px; height: 100px;">
                                    <?php if ($show_img && !empty($profile['photo_path']) && file_exists($profile['photo_path'])): ?>
                                        <img src="<?php echo e($profile['photo_path']); ?>" alt="Profile Picture">
                                    <?php else: ?>
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light">
                                            <?php if ($profile['gender'] === 'female'): ?>
                                                <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="var(--primary-color)" stroke-width="1.5">
                                                    <circle cx="12" cy="8" r="4"/>
                                                    <path d="M18 21a6 6 0 0 0-12 0"/>
                                                    <path d="M12 12v6m-2-2h4" stroke-linecap="round"/>
                                                </svg>
                                            <?php else: ?>
                                                <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="var(--primary-color)" stroke-width="1.5">
                                                    <circle cx="12" cy="8" r="4"/>
                                                    <path d="M18 21a6 6 0 0 0-12 0"/>
                                                </svg>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <h4 class="mb-1 fs-3"><?php echo e($profile['full_name']); ?></h4>
                                <p class="text-muted small mb-2">
                                    <?php echo e(calculate_age($profile['dob'])); ?> Yrs &bull; <?php echo e(ucfirst($profile['gender'])); ?>
                                </p>
                            </div>
                            <div class="border-top pt-2 text-secondary small">
                                <div class="row">
                                    <div class="col-6 mb-1">
                                        <strong>Religion:</strong> <?php echo e($profile['religion'] ?: 'Undisclosed'); ?>
                                    </div>
                                    <div class="col-6 mb-1">
                                        <strong>Caste:</strong> <?php echo e($profile['caste'] ?: 'Undisclosed'); ?>
                                    </div>
                                    <div class="col-6">
                                        <strong>Location:</strong> <?php echo e($profile['location'] ?: 'Undisclosed'); ?>
                                    </div>
                                    <div class="col-6">
                                        <strong>Profession:</strong> <?php echo e($profile['profession'] ?: 'Undisclosed'); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="d-grid mt-3">
                                <a href="profile.php?id=<?php echo $profile['id']; ?>" class="btn btn-primary-lami btn-sm py-2">View Full Profile</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
