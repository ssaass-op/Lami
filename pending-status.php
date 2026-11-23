<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
$status = $_SESSION['user_status'] ?? 'pending';
$name = $_SESSION['user_name'] ?? 'User';
?>
<div class="row justify-content-center py-5">
    <div class="col-md-8 col-lg-6 text-center">
        <div class="card card-lami p-5">
            <?php if ($status === 'pending'): ?>
                <div class="mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="#D4AF37" class="bi bi-clock-history animate-pulse" viewBox="0 0 16 16">
                      <path d="M8.515 1.019A7 7 0 0 0 8 1V0a8 8 0 0 1 .589.022zm2.004.45a7 7 0 0 0-.985-.299l.219-.976c.383.086.76.2 1.126.342zm1.37.71a7 7 0 0 0-.439-.74l.9-.44a8 8 0 0 1 .587.874zm.71 1.373a7 7 0 0 0-.74-.439l-.44.9a8 8 0 0 1 .874.587zm.45 2.005a7 7 0 0 0-.299-.985l-.976.219c.086.383.2.76.342 1.126zM16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8"/>
                      <path d="M8.5 4.466V8.5h3.5a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5V4.466a.5.5 0 0 1 1 0z"/>
                    </svg>
                </div>
                <h1 class="brand-font fs-1 mb-3">Verification in Progress</h1>
                <h4 class="text-muted fw-normal mb-4">Namaste, <?php echo e($name); ?>!</h4>
                <div class="pending-banner text-start mb-4">
                    <p class="mb-0 text-dark small">
                        <strong>Why is this required?</strong> To maintain the safety, authenticity, and sanctity of our Nepalese matrimonial network, Lami manually reviews every profile. We are verifying the identity marker/document you uploaded during registration.
                    </p>
                </div>
                <p class="text-secondary small mb-5">
                    Reviews are typically completed within <strong>24 hours</strong>. Once an administrator approves your profile, you will gain full access to view compatible matches, perform searches, and message other users.
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="pending-status.php" class="btn btn-accent-lami py-2">Check Status</a>
                    <a href="logout.php" class="btn btn-outline-lami py-2">Logout</a>
                </div>
            <?php elseif ($status === 'suspended'): ?>
                <div class="mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="#B3261E" class="bi bi-exclamation-triangle" viewBox="0 0 16 16">
                      <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.15.15 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.2.2 0 0 1-.054.06.1.1 0 0 1-.066.017H1.146a.1.1 0 0 1-.066-.017.2.2 0 0 1-.054-.06.18.18 0 0 1 .002-.183L7.884 2.073a.15.15 0 0 1 .054-.057zm1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767z"/>
                      <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
                    </svg>
                </div>
                <h1 class="brand-font text-danger fs-1 mb-3">Account Suspended</h1>
                <h4 class="text-muted fw-normal mb-4">Attention, <?php echo e($name); ?></h4>
                <div class="alert alert-danger text-start mb-4" role="alert">
                    <p class="mb-0 small">
                        Your account has been suspended by Lami administrators due to policy violations, report submissions, or failure to verify your identity credentials.
                    </p>
                </div>
                <p class="text-secondary small mb-5">
                    If you believe this is a mistake, or if you need to submit supplementary verification files, please reach out to the moderation team at <a href="mailto:support@lami.com" class="text-accent fw-bold text-decoration-none">support@lami.com</a>.
                </p>
                <div class="d-flex justify-content-center">
                    <a href="logout.php" class="btn btn-primary-lami py-2 px-4">Logout</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
