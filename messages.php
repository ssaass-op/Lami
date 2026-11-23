<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';
$current_user = $_SESSION['user_id'];
if (has_role(['admin', 'moderator'])) {
    header("Location: " . BASE_URL . "admin/dashboard.php");
    exit();
}
$chat_with_id = isset($_GET['chat_with']) ? intval($_GET['chat_with']) : 0;
$db = DB::getConnection();
try {
    $stmt = $db->prepare("
        SELECT 
            CASE WHEN i.sender_id = :my_id1 THEN i.receiver_id ELSE i.sender_id END AS user_id,
            u.full_name, p.gender, p.photo_path, ps.show_photo,
            (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = :my_id2 AND is_read = 0) AS unread_count
        FROM interests i
        JOIN users u ON (CASE WHEN i.sender_id = :my_id3 THEN i.receiver_id ELSE i.sender_id END) = u.id
        JOIN profiles p ON u.id = p.user_id
        JOIN privacy_settings ps ON u.id = ps.user_id
        WHERE (i.sender_id = :my_id4 OR i.receiver_id = :my_id5) AND i.status = 'accepted'
        ORDER BY u.full_name ASC
    ");
    $stmt->execute([ 
        'my_id1' => $current_user,
        'my_id2' => $current_user,
        'my_id3' => $current_user,
        'my_id4' => $current_user,
        'my_id5' => $current_user
    ]);
    $contacts = $stmt->fetchAll();
} catch (Exception $e) {
    die("Error loading contacts: " . htmlspecialchars($e->getMessage()));
}
$chat_target = null;
if ($chat_with_id > 0) {
    if (!is_connected($current_user, $chat_with_id)) {
        header("Location: " . BASE_URL . "messages.php");
        exit();
    }
    try {
        $stmt = $db->prepare("
            SELECT u.id, u.full_name, p.gender, p.photo_path, ps.show_photo 
            FROM users u
            JOIN profiles p ON u.id = p.user_id
            JOIN privacy_settings ps ON u.id = ps.user_id
            WHERE u.id = :id
        ");
        $stmt->execute(['id' => $chat_with_id]);
        $chat_target = $stmt->fetch();
    } catch (Exception $e) {
        die("Error loading chat partner details: " . htmlspecialchars($e->getMessage()));
    }
}
$csrf_token = generate_csrf_token();
require_once 'includes/header.php';
?>
<div class="row">
    <div class="col-12">
        <div class="chat-container row g-0">
            <div class="col-md-4 chat-contacts d-flex flex-column">
                <div class="p-3 border-bottom bg-white">
                    <h4 class="mb-0 fs-3">My Matches</h4>
                    <div class="form-text fs-8 text-secondary">Accepted connections available to chat</div>
                </div>
                <div class="flex-grow-1 overflow-auto">
                    <?php if (empty($contacts)): ?>
                        <div class="text-center py-5 px-3 text-secondary small">
                            No active matches to message.<br>
                            <a href="matches.php" class="btn btn-accent-lami btn-sm mt-3">Browse Matches</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($contacts as $contact): 
                            $is_active = ($contact['user_id'] === $chat_with_id);
                            $show_contact_img = ($contact['show_photo'] || is_connected($current_user, $contact['user_id']));
                            ?>
                            <div class="chat-contact-item d-flex align-items-center justify-content-between <?php echo $is_active ? 'active' : ''; ?>" onclick="window.location.href='messages.php?chat_with=<?php echo $contact['user_id']; ?>'">
                                <div class="d-flex align-items-center">
                                    <div class="profile-pic-container me-3" style="width: 45px; height: 45px; margin: 0;">
                                        <?php if ($show_contact_img && !empty($contact['photo_path']) && file_exists($contact['photo_path'])): ?>
                                            <img src="<?php echo e($contact['photo_path']); ?>" alt="Profile">
                                        <?php else: ?>
                                            <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light">
                                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--primary-color)" stroke-width="1.5">
                                                    <circle cx="12" cy="8" r="4"/>
                                                    <path d="M18 21a6 6 0 0 0-12 0"/>
                                                </svg>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <h5 class="mb-0 fs-4 text-dark"><?php echo e($contact['full_name']); ?></h5>
                                        <span class="text-muted small"><?php echo ucfirst($contact['gender']); ?></span>
                                    </div>
                                </div>
                                <?php if ($contact['unread_count'] > 0): ?>
                                    <span class="badge bg-danger rounded-pill"><?php echo $contact['unread_count']; ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-8 chat-window d-flex flex-column">
                <?php if ($chat_target): 
                    $show_target_img = ($chat_target['show_photo'] || is_connected($current_user, $chat_target['id']));
                    ?>
                    <div class="chat-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="profile-pic-container me-3" style="width: 45px; height: 45px; margin: 0;">
                                <?php if ($show_target_img && !empty($chat_target['photo_path']) && file_exists($chat_target['photo_path'])): ?>
                                    <img src="<?php echo e($chat_target['photo_path']); ?>" alt="Profile">
                                <?php else: ?>
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light">
                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--primary-color)" stroke-width="1.5">
                                            <circle cx="12" cy="8" r="4"/>
                                            <path d="M18 21a6 6 0 0 0-12 0"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h4 class="mb-0 fs-3"><a href="profile.php?id=<?php echo $chat_target['id']; ?>" class="text-decoration-none text-dark"><?php echo e($chat_target['full_name']); ?></a></h4>
                                <span class="text-muted small"><?php echo ucfirst($chat_target['gender']); ?> &bull; Active Connection</span>
                            </div>
                        </div>
                        <div>
                            <a href="profile.php?id=<?php echo $chat_target['id']; ?>" class="btn btn-outline-lami btn-sm py-1.5 px-3">View Profile</a>
                        </div>
                    </div>
                    <div class="chat-messages" id="chatMessages">
                        <div class="text-center text-muted small my-3">Connecting securely...</div>
                    </div>
                    <div class="chat-input-area">
                        <form id="chatForm" class="d-flex gap-2">
                            <input type="hidden" id="csrfToken" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" id="chatWithId" value="<?php echo $chat_target['id']; ?>">
                            <input type="hidden" id="currentUserId" value="<?php echo $current_user; ?>">
                            <input type="text" class="form-control py-2" id="chatMessageInput" placeholder="Type a message safely..." autocomplete="off" required>
                            <button type="submit" class="btn btn-primary-lami px-4 py-2" id="chatSendBtn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-send" viewBox="0 0 16 16">
                                  <path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576zm6.787-8.201L1.591 6.602l4.339 2.76z"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="flex-grow-1 d-flex flex-column align-items-center justify-content-center bg-light text-center p-5">
                        <div class="mb-4 text-accent">
                            <svg xmlns="http://www.w3.org/2000/svg" width="70" height="70" fill="currentColor" class="bi bi-chat-heart" viewBox="0 0 16 16">
                              <path fill-rule="evenodd" d="M2.965 12.695a1 1 0 0 0-.287-.37C1.97 11.54 1.5 10.47 1.5 9.27c0-2.612 2.677-4.77 6-4.77s6 2.158 6 4.77-2.677 4.77-6 4.77c-.42 0-.832-.041-1.229-.121a1 1 0 0 0-.755.15l-2.223 1.332a.5.5 0 0 1-.78-.426zm-.889.593a.5.5 0 0 1 .562-.178l1.402-.467c1.19.75 2.378.89 3.46.89 3.59 0 6.5-2.485 6.5-5.54S11.09 2.5 7.5 2.5 1 4.985 1 8.04c0 1.478.694 2.807 1.83 3.693L1.088 13.5a.5.5 0 0 1-.568-.781z"/>
                              <path d="M8 5.993c1.664-1.711 5.825 1.283 0 5.132-5.825-3.85-1.664-6.843 0-5.132"/>
                            </svg>
                        </div>
                        <h3 class="brand-font fs-2 text-dark">Secure Marriage Messenger</h3>
                        <p class="text-secondary small max-w-400">
                            Select a verified match from the left panel to begin a private, secure matchmaking conversation.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php if ($chat_target): ?>
    <script src="assets/js/chat.js"></script>
<?php endif; ?>
<?php require_once 'includes/footer.php'; ?>
