<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
$current_user = $_SESSION['user_id'];
if (has_role(['admin', 'moderator'])) {
    header("Location: " . BASE_URL . "admin/dashboard.php");
    exit();
}
$db = DB::getConnection();
try {
    $stmt = $db->prepare("SELECT gender FROM profiles WHERE user_id = :id");
    $stmt->execute(['id' => $current_user]);
    $my_profile = $stmt->fetch();
    if (!$my_profile) {
        die("Profile configuration missing. Please edit your profile first.");
    }
    $my_gender = $my_profile['gender'];
    $opposite_gender = ($my_gender === 'male') ? 'female' : 'male';
    $stmt = $db->prepare("SELECT * FROM matches_preferences WHERE user_id = :id");
    $stmt->execute(['id' => $current_user]);
    $my_prefs = $stmt->fetch();
    $stmt = $db->prepare("
        SELECT u.id, u.full_name, 
               p.gender, p.dob, p.religion, p.caste, p.education, p.profession, p.location, p.height, p.photo_path, p.marital_status, p.income_range,
               ps.show_photo
        FROM users u
        JOIN profiles p ON u.id = p.user_id
        JOIN privacy_settings ps ON u.id = ps.user_id
        WHERE u.status = 'verified' AND u.id != :my_id AND p.gender = :opposite_gender
    ");
    $stmt->execute([
        'my_id' => $current_user,
        'opposite_gender' => $opposite_gender
    ]);
    $candidates = $stmt->fetchAll();
    $scored_matches = [];
    foreach ($candidates as $candidate) {
        $score = 50; 
        if ($my_prefs) {
            $score = calculate_compatibility_score($my_prefs, $candidate);
        }
        $candidate['compatibility_score'] = $score;
        $scored_matches[] = $candidate;
    }
    usort($scored_matches, function($a, $b) {
        return $b['compatibility_score'] <=> $a['compatibility_score'];
    });
} catch (Exception $e) {
    die("Match engine error: " . htmlspecialchars($e->getMessage()));
}
require_once 'includes/header.php';
?>
<div class="row">
    <div class="col-12 mb-4">
        <div class="card card-lami p-4 text-center text-md-start" style="background: linear-gradient(135deg, #FAF8F5 0%, #FFF5EB 100%); border-left: 5px solid var(--accent-color);">
            <div class="row align-items-center">
                <div class="col-md-9">
                    <h2 class="brand-font fs-1 mb-2">Your Compatible Partner Recommendations</h2>
                    <p class="text-secondary mb-0">
                        These profiles are ranked automatically based on your Shaadi.com partner preferences (Age, Religion, Caste, Location, Education, Marital Status, Height Range, Profession, and Income).
                    </p>
                </div>
                <div class="col-md-3 text-md-end mt-3 mt-md-0">
                    <a href="profile-edit.php#partner" class="btn btn-accent-lami py-2.5">Edit Preferences</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12">
        <?php if (empty($scored_matches)): ?>
            <div class="card card-lami p-5 text-center">
                <svg width="60" height="60" class="mx-auto text-muted mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
                <h4>No Recommendations Found</h4>
                <p class="text-muted small">We couldn't find any verified <?php echo $opposite_gender; ?> profiles at the moment. Check back soon!</p>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <?php foreach ($scored_matches as $match): ?>
                    <?php 
                    $is_connected = is_connected($current_user, $match['id']);
                    $show_img = ($match['show_photo'] || $is_connected);
                    ?>
                    <div class="col">
                        <div class="card card-lami h-100 d-flex flex-column justify-content-between p-3 position-relative">
                            <div class="position-absolute" style="top: 10px; right: 10px; z-index: 10;">
                                <span class="compatibility-badge"><?php echo $match['compatibility_score']; ?>% Match</span>
                            </div>
                            <div class="text-center mb-3 mt-3">
                                <div class="profile-pic-container mb-3" style="width: 110px; height: 110px;">
                                    <?php if ($show_img && !empty($match['photo_path']) && file_exists($match['photo_path'])): ?>
                                        <img src="<?php echo e($match['photo_path']); ?>" alt="Match Photo">
                                    <?php else: ?>
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light">
                                            <?php if ($match['gender'] === 'female'): ?>
                                                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="var(--primary-color)" stroke-width="1.5">
                                                    <circle cx="12" cy="8" r="4"/>
                                                    <path d="M18 21a6 6 0 0 0-12 0"/>
                                                    <path d="M12 12v6m-2-2h4" stroke-linecap="round"/>
                                                </svg>
                                            <?php else: ?>
                                                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="var(--primary-color)" stroke-width="1.5">
                                                    <circle cx="12" cy="8" r="4"/>
                                                    <path d="M18 21a6 6 0 0 0-12 0"/>
                                                </svg>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <h4 class="mb-1 fs-3"><?php echo e($match['full_name']); ?></h4>
                                <p class="text-muted small mb-2">
                                    <?php echo e(calculate_age($match['dob'])); ?> Yrs &bull; <?php echo e(ucfirst($match['gender'])); ?>
                                </p>
                            </div>
                            <div class="border-top pt-2 text-secondary small">
                                <div class="row">
                                    <div class="col-6 mb-1">
                                        <strong>Religion:</strong> <?php echo e($match['religion'] ?: 'Undisclosed'); ?>
                                    </div>
                                    <div class="col-6 mb-1">
                                        <strong>Caste:</strong> <?php echo e($match['caste'] ?: 'Undisclosed'); ?>
                                    </div>
                                    <div class="col-6">
                                        <strong>Location:</strong> <?php echo e($match['location'] ?: 'Undisclosed'); ?>
                                    </div>
                                    <div class="col-6 text-truncate">
                                        <strong>Job:</strong> <?php echo e($match['profession'] ?: 'Undisclosed'); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="d-grid mt-3">
                                <a href="profile.php?id=<?php echo $match['id']; ?>" class="btn btn-primary-lami btn-sm py-2">Inspect Compatibility</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
