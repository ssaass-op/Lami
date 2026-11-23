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
$action = $_GET['action'] ?? '';
$target_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!empty($action) && $target_id > 0 && $target_id !== $current_user) {
    try {
        if ($action === 'send') {
            $stmt = $db->prepare("
                SELECT id FROM interests 
                WHERE (sender_id = :sender1 AND receiver_id = :receiver1)
                   OR (sender_id = :receiver2 AND receiver_id = :sender2)
            ");
            $stmt->execute([
                'sender1' => $current_user,
                'receiver1' => $target_id,
                'receiver2' => $target_id,
                'sender2' => $current_user
            ]);
            if (!$stmt->fetch()) {
                $stmt = $db->prepare("
                    INSERT INTO interests (sender_id, receiver_id, status) 
                    VALUES (:sender, :receiver, 'pending')
                ");
                $stmt->execute(['sender' => $current_user, 'receiver' => $target_id]);
            }
        } elseif ($action === 'accept') {
            $stmt = $db->prepare("
                UPDATE interests SET status = 'accepted' 
                WHERE sender_id = :sender AND receiver_id = :receiver AND status = 'pending'
            ");
            $stmt->execute(['sender' => $target_id, 'receiver' => $current_user]);
        } elseif ($action === 'reject') {
            $stmt = $db->prepare("
                UPDATE interests SET status = 'rejected' 
                WHERE sender_id = :sender AND receiver_id = :receiver AND status = 'pending'
            ");
            $stmt->execute(['sender' => $target_id, 'receiver' => $current_user]);
        } elseif ($action === 'revoke') {
            $stmt = $db->prepare("
                DELETE FROM interests 
                WHERE (sender_id = :ua1 AND receiver_id = :ub1)
                   OR (sender_id = :ub2 AND receiver_id = :ua2)
            ");
            $stmt->execute([
                'ua1' => $current_user,
                'ub1' => $target_id,
                'ub2' => $target_id,
                'ua2' => $current_user
            ]);
        }
        $referrer = $_SERVER['HTTP_REFERER'] ?? 'connect.php';
        header("Location: " . $referrer);
        exit();
    } catch (Exception $e) {
        die("Connection state change failed: " . htmlspecialchars($e->getMessage()));
    }
}
try {
    $stmt = $db->prepare("
        SELECT i.sender_id AS user_id, u.full_name, p.gender, p.dob, p.location, p.photo_path, ps.show_photo
        FROM interests i
        JOIN users u ON i.sender_id = u.id
        JOIN profiles p ON i.sender_id = p.user_id
        JOIN privacy_settings ps ON i.sender_id = ps.user_id
        WHERE i.receiver_id = :my_id AND i.status = 'pending'
        ORDER BY i.created_at DESC
    ");
    $stmt->execute(['my_id' => $current_user]);
    $incoming_requests = $stmt->fetchAll();
    $stmt = $db->prepare("
        SELECT i.receiver_id AS user_id, u.full_name, p.gender, p.dob, p.location, p.photo_path, ps.show_photo
        FROM interests i
        JOIN users u ON i.receiver_id = u.id
        JOIN profiles p ON i.receiver_id = p.user_id
        JOIN privacy_settings ps ON i.receiver_id = ps.user_id
        WHERE i.sender_id = :my_id AND i.status = 'pending'
        ORDER BY i.created_at DESC
    ");
    $stmt->execute(['my_id' => $current_user]);
    $outgoing_requests = $stmt->fetchAll();
    $stmt = $db->prepare("
        SELECT 
            CASE WHEN i.sender_id = :my_id1 THEN i.receiver_id ELSE i.sender_id END AS user_id,
            u.full_name, p.gender, p.dob, p.location, p.photo_path, ps.show_photo
        FROM interests i
        JOIN users u ON (CASE WHEN i.sender_id = :my_id2 THEN i.receiver_id ELSE i.sender_id END) = u.id
        JOIN profiles p ON u.id = p.user_id
        JOIN privacy_settings ps ON u.id = ps.user_id
        WHERE (i.sender_id = :my_id3 OR i.receiver_id = :my_id4) AND i.status = 'accepted'
        ORDER BY i.created_at DESC
    ");
    $stmt->execute([
        'my_id1' => $current_user,
        'my_id2' => $current_user,
        'my_id3' => $current_user,
        'my_id4' => $current_user
    ]);
    $active_connections = $stmt->fetchAll();
} catch (Exception $e) {
    die("Connection loading failed: " . htmlspecialchars($e->getMessage()));
}
require_once 'includes/header.php';
?>
<h1 class="brand-font fs-1 mb-4 text-center">My Connections Dashboard</h1>
<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card card-lami p-4 h-100">
            <h3 class="border-bottom pb-2 mb-3 fs-2"><i class="bi bi-heart-fill text-danger"></i> Active Matches (<?php echo count($active_connections); ?>)</h3>
            <p class="text-muted small mb-4">You have mutually accepted interest requests with these members. You can view contact details and chat with each other.</p>
            <?php if (empty($active_connections)): ?>
                <div class="text-center py-4 text-secondary">
                    <p class="mb-0">No active matches yet.</p>
                    <a href="search.php" class="btn btn-accent-lami btn-sm mt-3">Search Profiles</a>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($active_connections as $conn): ?>
                        <div class="list-group-item d-flex align-items-center justify-content-between px-0 py-3">
                            <div class="d-flex align-items-center">
                                <div class="profile-pic-container me-3" style="width: 50px; height: 50px; margin: 0;">
                                    <?php if (!empty($conn['photo_path']) && file_exists($conn['photo_path'])): ?>
                                        <img src="<?php echo e($conn['photo_path']); ?>" alt="Profile">
                                    <?php else: ?>
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light">
                                            <svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="var(--primary-color)" stroke-width="1.5">
                                                <circle cx="12" cy="8" r="4"/>
                                                <path d="M18 21a6 6 0 0 0-12 0"/>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h5 class="mb-0 fs-4"><a href="profile.php?id=<?php echo $conn['user_id']; ?>" class="text-decoration-none text-dark"><?php echo e($conn['full_name']); ?></a></h5>
                                    <span class="text-muted small"><?php echo calculate_age($conn['dob']); ?> Yrs &bull; <?php echo e($conn['location']); ?></span>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="messages.php?chat_with=<?php echo $conn['user_id']; ?>" class="btn btn-primary-lami btn-sm py-1.5 px-3">Chat</a>
                                <a href="connect.php?action=revoke&id=<?php echo $conn['user_id']; ?>" class="btn btn-outline-danger btn-sm py-1.5 px-2.5" onclick="return confirm('Are you sure you want to disconnect from this match?')">Disconnect</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-lami p-4 mb-4">
            <h3 class="border-bottom pb-2 mb-3 fs-2"><i class="bi bi-box-arrow-in-down text-accent"></i> Incoming Requests (<?php echo count($incoming_requests); ?>)</h3>
            <?php if (empty($incoming_requests)): ?>
                <div class="text-center py-3 text-secondary small">
                    No pending match requests.
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($incoming_requests as $req): ?>
                        <div class="list-group-item d-flex align-items-center justify-content-between px-0 py-3">
                            <div class="d-flex align-items-center">
                                <div class="profile-pic-container me-3" style="width: 50px; height: 50px; margin: 0;">
                                    <?php if ($req['show_photo'] && !empty($req['photo_path']) && file_exists($req['photo_path'])): ?>
                                        <img src="<?php echo e($req['photo_path']); ?>" alt="Profile">
                                    <?php else: ?>
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light">
                                            <svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="var(--primary-color)" stroke-width="1.5">
                                                <circle cx="12" cy="8" r="4"/>
                                                <path d="M18 21a6 6 0 0 0-12 0"/>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h5 class="mb-0 fs-4"><a href="profile.php?id=<?php echo $req['user_id']; ?>" class="text-decoration-none text-dark"><?php echo e($req['full_name']); ?></a></h5>
                                    <span class="text-muted small"><?php echo calculate_age($req['dob']); ?> Yrs &bull; <?php echo e($req['location']); ?></span>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="connect.php?action=accept&id=<?php echo $req['user_id']; ?>" class="btn btn-success btn-sm py-1.5 px-3">Accept</a>
                                <a href="connect.php?action=reject&id=<?php echo $req['user_id']; ?>" class="btn btn-danger btn-sm py-1.5 px-3">Decline</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="card card-lami p-4">
            <h3 class="border-bottom pb-2 mb-3 fs-2"><i class="bi bi-send-fill text-muted"></i> Outgoing Sent Requests (<?php echo count($outgoing_requests); ?>)</h3>
            <?php if (empty($outgoing_requests)): ?>
                <div class="text-center py-3 text-secondary small">
                    No active sent requests.
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($outgoing_requests as $req): ?>
                        <div class="list-group-item d-flex align-items-center justify-content-between px-0 py-3">
                            <div class="d-flex align-items-center">
                                <div class="profile-pic-container me-3" style="width: 50px; height: 50px; margin: 0;">
                                    <?php if ($req['show_photo'] && !empty($req['photo_path']) && file_exists($req['photo_path'])): ?>
                                        <img src="<?php echo e($req['photo_path']); ?>" alt="Profile">
                                    <?php else: ?>
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light">
                                            <svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="var(--primary-color)" stroke-width="1.5">
                                                <circle cx="12" cy="8" r="4"/>
                                                <path d="M18 21a6 6 0 0 0-12 0"/>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h5 class="mb-0 fs-4"><a href="profile.php?id=<?php echo $req['user_id']; ?>" class="text-decoration-none text-dark"><?php echo e($req['full_name']); ?></a></h5>
                                    <span class="text-muted small"><?php echo calculate_age($req['dob']); ?> Yrs &bull; <?php echo e($req['location']); ?></span>
                                </div>
                            </div>
                            <div>
                                <a href="connect.php?action=revoke&id=<?php echo $req['user_id']; ?>" class="btn btn-outline-secondary btn-sm py-1.5 px-3">Cancel Request</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
