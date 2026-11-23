<?php
require_once '../includes/auth_check.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
if (!has_role(['admin', 'moderator'])) {
    die("Access Denied: Administrative privileges required.");
}
$admin_id = $_SESSION['user_id'];
$db = DB::getConnection();
try {
    $total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $pending_verifications = $db->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
    $active_matches = $db->query("SELECT COUNT(*) FROM interests WHERE status = 'accepted'")->fetchColumn();
    $stmt = $db->prepare("
        SELECT u.id, u.full_name, u.email, u.phone, p.gender, p.dob, p.identity_doc_path 
        FROM users u 
        JOIN profiles p ON u.id = p.user_id 
        WHERE u.status = 'pending'
        ORDER BY u.created_at ASC
    ");
    $stmt->execute();
    $pendings = $stmt->fetchAll();
    $stmt = $db->prepare("
        SELECT id, full_name, email, phone, role, status 
        FROM users 
        WHERE id != :admin_id
        ORDER BY created_at DESC
    ");
    $stmt->execute(['admin_id' => $admin_id]);
    $users = $stmt->fetchAll();
    $audit_logs = $db->query("
        SELECT a.id, a.action, a.timestamp, u.full_name AS admin_name, t.full_name AS target_name 
        FROM audit_logs a 
        JOIN users u ON a.admin_id = u.id 
        LEFT JOIN users t ON a.target_user_id = t.id 
        ORDER BY a.timestamp DESC 
        LIMIT 10
    ")->fetchAll();
} catch (Exception $e) {
    die("Admin panel loading failed: " . htmlspecialchars($e->getMessage()));
}
$csrf_token = generate_csrf_token();
require_once '../includes/header.php';
?>
<div class="row mb-4">
    <div class="col-12">
        <h1 class="brand-font fs-1 mb-2">Lami Moderation Panel</h1>
        <p class="text-secondary small">Review credentials, verify profiles, and manage system roles</p>
    </div>
</div>
<div class="row mb-5 g-4">
    <div class="col-md-4">
        <div class="stat-box h-100">
            <div class="stat-number"><?php echo $total_users; ?></div>
            <div class="text-uppercase tracking-wider small fw-bold text-muted">Total Registered Users</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-box h-100 border-warning bg-light">
            <div class="stat-number text-warning"><?php echo $pending_verifications; ?></div>
            <div class="text-uppercase tracking-wider small fw-bold text-muted">Pending Verifications</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-box h-100 border-success">
            <div class="stat-number text-success"><?php echo $active_matches; ?></div>
            <div class="text-uppercase tracking-wider small fw-bold text-muted">Active Matched Couples</div>
        </div>
    </div>
</div>
<div class="card card-lami mb-5">
    <div class="card-header card-header-lami bg-light">
        <h3 class="mb-0 fs-2 text-warning"><i class="bi bi-shield-check"></i> Identity Verification Queue</h3>
        <p class="text-muted small mb-0">Inspect uploaded identity documents and approve accounts</p>
    </div>
    <div class="card-body p-4">
        <?php if (empty($pendings)): ?>
            <div class="text-center py-4 text-secondary small">
                No users awaiting verification. All clean!
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>User Name</th>
                            <th>Gender / Age</th>
                            <th>Contact Info</th>
                            <th>Uploaded Identity Doc</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendings as $p): ?>
                            <tr>
                                <td>
                                    <strong><?php echo e($p['full_name']); ?></strong>
                                </td>
                                <td>
                                    <?php echo ucfirst($p['gender']); ?> &bull; <?php echo calculate_age($p['dob']); ?> Yrs
                                </td>
                                <td>
                                    <span class="small d-block"><?php echo e($p['email']); ?></span>
                                    <span class="small text-muted"><?php echo e($p['phone']); ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($p['identity_doc_path']) && file_exists('../' . $p['identity_doc_path'])): ?>
                                        <a href="<?php echo '../' . e($p['identity_doc_path']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-1 px-2.5">
                                            View Document File
                                        </a>
                                    <?php else: ?>
                                        <span class="text-danger small">File missing</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <form action="verify.php" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="target_id" value="<?php echo $p['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success btn-sm py-1 px-3">Approve</button>
                                        </form>
                                        <form action="verify.php" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="target_id" value="<?php echo $p['id']; ?>">
                                            <input type="hidden" name="action" value="suspend">
                                            <button type="submit" class="btn btn-danger btn-sm py-1 px-3" onclick="return confirm('Are you sure you want to reject and suspend this user?')">Reject</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card card-lami h-100">
            <div class="card-header card-header-lami">
                <h3 class="mb-0 fs-2">Platform Users</h3>
                <p class="text-muted small mb-0">Modify statuses and administrative roles</p>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table align-middle table-sm">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th class="text-end">Change State</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td>
                                        <strong><a href="../profile.php?id=<?php echo $u['id']; ?>" target="_blank" class="text-decoration-none text-dark"><?php echo e($u['full_name']); ?></a></strong>
                                        <div class="small text-muted"><?php echo e($u['email']); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo e(ucfirst($u['role'])); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $u['status'] === 'verified' ? 'success' : ($u['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                            <?php echo e(ucfirst($u['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-outline-secondary btn-sm py-0.5 px-2 dropdown-toggle fs-8" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                Manage
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li>
                                                    <form action="verify.php" method="POST">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                        <input type="hidden" name="target_id" value="<?php echo $u['id']; ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit" class="dropdown-item py-1">Set Verified</button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form action="verify.php" method="POST">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                        <input type="hidden" name="target_id" value="<?php echo $u['id']; ?>">
                                                        <input type="hidden" name="action" value="suspend">
                                                        <button type="submit" class="dropdown-item text-danger py-1">Suspend / Ban</button>
                                                    </form>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="verify.php" method="POST">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                        <input type="hidden" name="target_id" value="<?php echo $u['id']; ?>">
                                                        <input type="hidden" name="action" value="make_moderator">
                                                        <button type="submit" class="dropdown-item py-1">Set Moderator</button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form action="verify.php" method="POST">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                        <input type="hidden" name="target_id" value="<?php echo $u['id']; ?>">
                                                        <input type="hidden" name="action" value="make_standard">
                                                        <button type="submit" class="dropdown-item py-1">Set Standard User</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card card-lami h-100">
            <div class="card-header card-header-lami">
                <h3 class="mb-0 fs-2"><i class="bi bi-clock-history"></i> System Activity Logs</h3>
                <p class="text-muted small mb-0">Recent administrative actions</p>
            </div>
            <div class="card-body p-3">
                <?php if (empty($audit_logs)): ?>
                    <div class="text-center py-5 text-secondary small">
                        No activity logged yet.
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush" style="max-height: 480px; overflow-y: auto;">
                        <?php foreach ($audit_logs as $log): ?>
                            <div class="list-group-item px-0 py-2.5">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="small text-dark"><?php echo e($log['admin_name']); ?></strong>
                                    <span class="fs-9 text-muted"><?php echo date('M d, H:i', strtotime($log['timestamp'])); ?></span>
                                </div>
                                <p class="mb-0 small text-secondary">
                                    <?php echo e($log['action']); ?> <?php echo $log['target_name'] ? 'on <strong>' . e($log['target_name']) . '</strong>' : ''; ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
