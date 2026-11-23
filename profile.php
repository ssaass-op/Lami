<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
$current_user = $_SESSION['user_id'];
$is_admin_mod = has_role(['admin', 'moderator']);
$target_user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($target_user_id <= 0) {
    header("Location: " . BASE_URL . "matches.php");
    exit();
}
$db = DB::getConnection();
$stmt = $db->prepare("
    SELECT u.id, u.full_name, u.email, u.phone, u.status, u.role,
           p.gender, p.dob, p.religion, p.caste, p.education, p.profession, 
           p.location, p.height, p.bio, p.income_range, p.marital_status, p.photo_path,
           ps.show_phone, ps.show_photo, ps.show_income
    FROM users u
    JOIN profiles p ON u.id = p.user_id
    JOIN privacy_settings ps ON u.id = ps.user_id
    WHERE u.id = :id
");
$stmt->execute(['id' => $target_user_id]);
$target = $stmt->fetch();
if (!$target) {
    die("Profile not found.");
}
if ($target['status'] !== 'verified' && !$is_admin_mod && $target['id'] !== $current_user) {
    die("This profile is not currently verified or has been suspended.");
}
$connection = get_connection_details($current_user, $target['id']);
$is_connected = ($connection && $connection['status'] === 'accepted');
$bypass_privacy = ($is_admin_mod || $current_user === $target['id']);
$visible_photo = false;
if ($bypass_privacy || $target['show_photo'] || $is_connected) {
    $visible_photo = true;
}
$visible_phone = false;
if ($bypass_privacy || ($target['show_phone'] && $is_connected)) {
    $visible_phone = true;
}
$visible_income = false;
if ($bypass_privacy || ($target['show_income'] && $is_connected)) {
    $visible_income = true;
}
require_once 'includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="mb-4">
            <a href="javascript:history.back()" class="text-accent fw-bold text-decoration-none small d-inline-flex align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left me-2" viewBox="0 0 16 16">
                  <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/>
                </svg>
                Back to previous page
            </a>
        </div>
        <div class="card card-lami mb-4 overflow-hidden">
            <div class="p-4 text-white text-center" style="background: linear-gradient(135deg, var(--primary-color) 0%, #4a0000 100%);">
                <div class="profile-pic-container mb-3" style="width: 140px; height: 140px;">
                    <?php if ($visible_photo && !empty($target['photo_path']) && file_exists($target['photo_path'])): ?>
                        <img src="<?php echo e($target['photo_path']); ?>" alt="Profile Photo">
                    <?php else: ?>
                        <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light">
                            <?php if ($target['gender'] === 'female'): ?>
                                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="var(--primary-color)" stroke-width="1.5">
                                    <circle cx="12" cy="8" r="4"/>
                                    <path d="M18 21a6 6 0 0 0-12 0"/>
                                    <path d="M12 12v6m-2-2h4" stroke-linecap="round"/>
                                </svg>
                            <?php else: ?>
                                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="var(--primary-color)" stroke-width="1.5">
                                    <circle cx="12" cy="8" r="4"/>
                                    <path d="M18 21a6 6 0 0 0-12 0"/>
                                </svg>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <h2 class="text-white mb-1 fs-1"><?php echo e($target['full_name']); ?></h2>
                <p class="mb-0 text-white-50 small">
                    <?php echo e(ucfirst($target['gender'])); ?> &bull; <?php echo e(calculate_age($target['dob'])); ?> Years &bull; <?php echo e(e($target['location'])); ?>
                </p>
                <?php if ($target['id'] !== $current_user): ?>
                    <div class="mt-3">
                        <?php if (!$connection): ?>
                            <a href="connect.php?action=send&id=<?php echo $target['id']; ?>" class="btn btn-accent-lami btn-sm px-4">Send Interest Request</a>
                        <?php elseif ($connection['status'] === 'pending'): ?>
                            <?php if ($connection['sender_id'] === $current_user): ?>
                                <span class="badge bg-warning text-dark p-2 fs-7">Interest Request Pending</span>
                            <?php else: ?>
                                <div class="d-inline-flex gap-2">
                                    <a href="connect.php?action=accept&id=<?php echo $target['id']; ?>" class="btn btn-success btn-sm px-3">Accept Interest</a>
                                    <a href="connect.php?action=reject&id=<?php echo $target['id']; ?>" class="btn btn-danger btn-sm px-3">Reject</a>
                                </div>
                            <?php endif; ?>
                        <?php elseif ($connection['status'] === 'accepted'): ?>
                            <div class="d-inline-flex align-items-center gap-2">
                                <span class="badge bg-success p-2 fs-7">Connected Match</span>
                                <a href="messages.php?chat_with=<?php echo $target['id']; ?>" class="btn btn-accent-lami btn-sm">Open Chat Room</a>
                            </div>
                        <?php elseif ($connection['status'] === 'rejected'): ?>
                            <span class="badge bg-secondary p-2 fs-7">Request Declined</span>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="mt-2">
                        <span class="badge bg-light text-dark">This is your profile</span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-body p-4">
                <div class="mb-4">
                    <h4 class="border-bottom pb-2 mb-3">About Me</h4>
                    <p class="text-secondary"><?php echo nl2br(e($target['bio'] ?: 'No bio details provided.')); ?></p>
                </div>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h4 class="border-bottom pb-2 mb-3">Personal & Social</h4>
                        <table class="table table-borderless table-sm text-secondary">
                            <tr>
                                <th width="40%">Religion:</th>
                                <td><?php echo e($target['religion'] ?: 'Undisclosed'); ?></td>
                            </tr>
                            <tr>
                                <th>Caste/Ethnicity:</th>
                                <td><?php echo e($target['caste'] ?: 'Undisclosed'); ?></td>
                            </tr>
                            <tr>
                                <th>Marital Status:</th>
                                <td><?php echo e(ucwords(str_replace('_', ' ', $target['marital_status']))); ?></td>
                            </tr>
                            <tr>
                                <th>Height:</th>
                                <td><?php echo $target['height'] ? e($target['height']) . ' cm' : 'Undisclosed'; ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h4 class="border-bottom pb-2 mb-3">Education & Career</h4>
                        <table class="table table-borderless table-sm text-secondary">
                            <tr>
                                <th width="40%">Education:</th>
                                <td><?php echo e($target['education'] ?: 'Undisclosed'); ?></td>
                            </tr>
                            <tr>
                                <th>Profession:</th>
                                <td><?php echo e($target['profession'] ?: 'Undisclosed'); ?></td>
                            </tr>
                            <tr>
                                <th>Current Location:</th>
                                <td><?php echo e($target['location'] ?: 'Undisclosed'); ?></td>
                            </tr>
                            <tr>
                                <th>Annual Income:</th>
                                <td>
                                    <?php if ($visible_income): ?>
                                        <?php echo e($target['income_range'] ?: 'Undisclosed'); ?>
                                    <?php else: ?>
                                        <span class="text-muted italic small"><i class="bi bi-lock-fill"></i> Hidden (Visible to connected matches)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="p-3 bg-light rounded mb-2 border">
                    <h4 class="mb-3">Contact Information</h4>
                    <div class="row">
                        <div class="col-md-6 mb-2 mb-md-0">
                            <strong>Email Address:</strong><br>
                            <?php if ($is_connected || $bypass_privacy): ?>
                                <span class="text-secondary"><?php echo e($target['email']); ?></span>
                            <?php else: ?>
                                <span class="text-muted italic small"><i class="bi bi-lock-fill"></i> Hidden (Visible to connected matches)</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Phone Number:</strong><br>
                            <?php if ($visible_phone): ?>
                                <span class="text-secondary"><?php echo e($target['phone']); ?></span>
                            <?php else: ?>
                                <span class="text-muted italic small"><i class="bi bi-lock-fill"></i> Hidden (Visible to connected matches)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
