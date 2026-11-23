<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
$db = DB::getConnection();
try {
    $verified_users = $db->query("SELECT COUNT(*) FROM users WHERE status = 'verified' AND role = 'standard'")->fetchColumn();
    $matches_created = $db->query("SELECT COUNT(*) FROM interests WHERE status = 'accepted'")->fetchColumn();
} catch (Exception $e) {
    $verified_users = 5; 
    $matches_created = 2; 
}
require_once 'includes/header.php';
?>
<div class="row hero-section rounded-4 overflow-hidden mb-5 shadow-lg">
    <div class="col-xl-11 mx-auto px-2 px-md-4 hero-content-wrap">
        <h1 class="text-white">Where Tradition Meets Secure Connection</h1>
        <p class="text-white-50 max-w-700 mx-auto">
            Nepal's premium matrimonial platform. Find genuine, verified Nepalese matches with privacy, trust, and traditional family values.
        </p>
        <div class="matchmaker-container text-start">
            <form id="heroSearchForm" action="<?php echo is_logged_in() ? 'search.php' : 'register.php'; ?>" method="GET">
                <div class="matchmaker-bar">
                    <div class="matchmaker-col">
                        <label for="hero_gender" class="matchmaker-label">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-person-heart" viewBox="0 0 16 16">
                              <path d="M9 5a3 3 0 1 1-6 0 3 3 0 0 1 6 0m-9 8c0 1 1 1 1 1h10s1 0 1-1-1-4-6-4-6 3-6 4m13.5-8.09c1.387-1.425 4.855 1.07 0 4.277-4.854-3.207-1.387-5.702 0-4.276z"/>
                            </svg>
                            I'm looking for a
                        </label>
                        <select class="form-select matchmaker-select" id="hero_gender" name="gender">
                            <option value="female" selected>Woman</option>
                            <option value="male">Man</option>
                            <option value="">Any / Open</option>
                        </select>
                    </div>
                    <div class="matchmaker-col matchmaker-col-age">
                        <label class="matchmaker-label">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-calendar-event" viewBox="0 0 16 16">
                              <path d="M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5z"/>
                              <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
                            </svg>
                            Aged
                        </label>
                        <div class="matchmaker-age-group">
                            <select class="form-select matchmaker-select" id="hero_min_age" name="min_age">
                                <?php for ($a = 18; $a <= 70; $a++): ?>
                                    <option value="<?php echo $a; ?>" <?php echo $a === 20 ? 'selected' : ''; ?>><?php echo $a; ?></option>
                                <?php endfor; ?>
                            </select>
                            <span class="matchmaker-age-sep">to</span>
                            <select class="form-select matchmaker-select" id="hero_max_age" name="max_age">
                                <?php for ($a = 18; $a <= 70; $a++): ?>
                                    <option value="<?php echo $a; ?>" <?php echo $a === 35 ? 'selected' : ''; ?>><?php echo $a; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="matchmaker-col">
                        <label for="hero_religion" class="matchmaker-label">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-star-fill" viewBox="0 0 16 16">
                              <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
                            </svg>
                            Of religion
                        </label>
                        <select class="form-select matchmaker-select" id="hero_religion" name="religion">
                            <option value="" selected>Any / Open to all</option>
                            <option value="Hindu">Hindu</option>
                            <option value="Buddhist">Buddhist</option>
                            <option value="Islam">Islam</option>
                            <option value="Christian">Christian</option>
                            <option value="Kirat">Kirat</option>
                            <option value="Sikh">Sikh</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="matchmaker-col">
                        <label for="hero_location" class="matchmaker-label">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-geo-alt-fill" viewBox="0 0 16 16">
                              <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10m0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6"/>
                            </svg>
                            And living in
                        </label>
                        <select class="form-select matchmaker-select" id="hero_location" name="location">
                            <option value="" selected>Any location</option>
                            <option value="Biratnagar">Biratnagar</option>
                            <option value="Kathmandu">Kathmandu</option>
                            <option value="Pokhara">Pokhara</option>
                            <option value="Lalitpur">Lalitpur</option>
                            <option value="Bhaktapur">Bhaktapur</option>
                            <option value="Chitwan">Chitwan</option>
                            <option value="Dharan">Dharan</option>
                            <option value="Butwal">Butwal</option>
                            <option value="Abroad">Abroad / International</option>
                        </select>
                    </div>
                    <div class="matchmaker-col matchmaker-col-btn">
                        <button type="submit" class="btn btn-matchmaker-cta" id="btnHeroSubmit">
                            <span>Let's Begin</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-arrow-right-circle-fill" viewBox="0 0 16 16">
                              <path d="M8 0a8 8 0 1 1 0 16A8 8 0 0 1 8 0M4.5 7.5a.5.5 0 0 0 0 1h5.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?php if (!is_logged_in()): ?>
<div class="modal fade" id="signUpPromptModal" tabindex="-1" aria-labelledby="signUpPromptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content card-lami border-0 shadow-lg">
            <div class="modal-header border-0 pb-0 pt-4 px-4 text-center d-block position-relative">
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="mb-2">
                    <span class="compatibility-badge">Match Preferences Ready</span>
                </div>
                <h3 class="modal-title fs-2 brand-font mb-1" id="signUpPromptModalLabel">Begin Your Lami Journey</h3>
                <p class="text-muted small mb-0">Create your free profile to view matching Nepalese candidates</p>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="bg-light p-3 rounded-3 mb-4 text-start border">
                    <h6 class="fw-bold text-uppercase fs-8 tracking-wider text-muted mb-2">Your Selected Criteria</h6>
                    <div id="criteriaSummaryList" class="d-flex flex-wrap gap-2">
                    </div>
                </div>
                <p class="text-secondary small mb-4">
                    To maintain privacy and identity verification for all Nepalese families, candidate details are accessible to registered members.
                </p>
                <div class="d-grid gap-2">
                    <a href="#" id="btnModalRegister" class="btn btn-primary-lami btn-lg py-2.5">
                        Create Free Account & View Matches
                    </a>
                    <a href="login.php" class="btn btn-outline-lami py-2">
                        Already Have an Account? Log In
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('heroSearchForm');
    if (!searchForm) return;
    searchForm.addEventListener('submit', function(e) {
        <?php if (!is_logged_in()): ?>
            e.preventDefault();
            const genderSelect = document.getElementById('hero_gender');
            const minAgeSelect = document.getElementById('hero_min_age');
            const maxAgeSelect = document.getElementById('hero_max_age');
            const religionSelect = document.getElementById('hero_religion');
            const locationSelect = document.getElementById('hero_location');
            const genderVal = genderSelect.value;
            const minAgeVal = minAgeSelect.value;
            const maxAgeVal = maxAgeSelect.value;
            const religionVal = religionSelect.value;
            const locationVal = locationSelect.value;
            // Build Criteria summary badges
            const summaryContainer = document.getElementById('criteriaSummaryList');
            summaryContainer.innerHTML = '';
            let genderText = 'Any Gender';
            if (genderVal === 'female') genderText = 'Woman';
            if (genderVal === 'male') genderText = 'Man';
            const badges = [
                `Looking for: <strong>${genderText}</strong>`,
                `Age: <strong>${minAgeVal} - ${maxAgeVal} yrs</strong>`,
                `Religion: <strong>${religionVal || 'Any'}</strong>`,
                `Location: <strong>${locationVal || 'Any'}</strong>`
            ];
            badges.forEach(text => {
                const span = document.createElement('span');
                span.className = 'badge bg-white text-dark border p-2 fw-normal';
                span.innerHTML = text;
                summaryContainer.appendChild(span);
            });
            // Set registration link URL with search params
            const params = new URLSearchParams({
                gender: genderVal,
                min_age: minAgeVal,
                max_age: maxAgeVal,
                religion: religionVal,
                location: locationVal
            });
            const regBtn = document.getElementById('btnModalRegister');
            regBtn.href = `register.php?${params.toString()}`;
            // Show Bootstrap Modal
            const modalEl = document.getElementById('signUpPromptModal');
            const bsModal = new bootstrap.Modal(modalEl);
            bsModal.show();
        <?php endif; ?>
    });
});
</script>
<?php endif; ?>
<div class="row mb-5 text-center g-4">
    <div class="col-md-6 col-lg-4 offset-lg-2">
        <div class="stat-box">
            <div class="stat-number"><?php echo $verified_users; ?>+</div>
            <div class="text-uppercase tracking-wider small fw-bold text-muted">Verified Active Profiles</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="stat-box">
            <div class="stat-number"><?php echo $matches_created; ?>+</div>
            <div class="text-uppercase tracking-wider small fw-bold text-muted">Successful Matches Connected</div>
        </div>
    </div>
</div>
<div class="row align-items-center mb-5 py-4">
    <div class="col-md-6 mb-4 mb-md-0">
        <h2 class="brand-font fs-1 mb-3">Our Three Pillars of Matching</h2>
        <p class="text-secondary">
            Lami was built to replace unverified social media profiles and insecure systems. We bring structure and safety to Nepalese matchmaking.
        </p>
        <div class="d-flex align-items-start mb-3">
            <div class="me-3 mt-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="var(--primary-color)" class="bi bi-patch-check-fill" viewBox="0 0 16 16">
                  <path d="M10.067.87a2.89 2.89 0 0 0-4.134 0l-.622.638-.89-.011a2.89 2.89 0 0 0-2.924 2.924l.01.89-.636.622a2.89 2.89 0 0 0 0 4.134l.637.622-.011.89a2.89 2.89 0 0 0 2.924 2.924l.89-.01.622.636a2.89 2.89 0 0 0 4.134 0l.622-.637.89.011a2.89 2.89 0 0 0 2.924-2.924l-.01-.89.636-.622a2.89 2.89 0 0 0 0-4.134l-.637-.622.011-.89a2.89 2.89 0 0 0-2.924-2.924l-.89.01zm.244 5.178L8.02 8.827a.25.25 0 0 1-.365.015L6.155 7.371a.25.25 0 1 1 .361-.346l1.137 1.18 2.302-2.733a.25.25 0 0 1 .38.313"/>
                </svg>
            </div>
            <div>
                <h5 class="fw-bold mb-1">Strict Identity Verification Gate</h5>
                <p class="text-secondary small mb-0">Every profile must submit a legal Nepalese identity document (Citizenship or Passport) which is reviewed and verified by our admin team before they can browse matches.</p>
            </div>
        </div>
        <div class="d-flex align-items-start mb-3">
            <div class="me-3 mt-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="var(--primary-color)" class="bi bi-shield-lock-fill" viewBox="0 0 16 16">
                  <path fill-rule="evenodd" d="M8 0c-.69 0-1.843.265-2.928.56-1.11.3-2.229.655-2.887.87a1.54 1.54 0 0 0-1.044 1.262c-.596 4.477.787 7.795 2.465 9.99a11.8 11.8 0 0 0 2.517 2.453c.386.273.744.482 1.048.625.28.132.581.24.829.24s.548-.108.829-.24a7 7 0 0 0 1.048-.625 11.8 11.8 0 0 0 2.517-2.453c1.678-2.195 3.061-5.513 2.465-9.99a1.54 1.54 0 0 0-1.044-1.263 60 60 0 0 0-2.887-.87C9.843.266 8.69 0 8 0m0 5a1.5 1.5 0 0 1 .5 2.915V9.5a.5.5 0 0 1-1 0V7.915A1.5 1.5 0 0 1 8 5"/>
                </svg>
            </div>
            <div>
                <h5 class="fw-bold mb-1">Granular Privacy Control</h5>
                <p class="text-secondary small mb-0">You are in control. Hide your photo, phone number, or income range. Non-connected profiles will only see standard, gender-themed placeholders until you accept their interest.</p>
            </div>
        </div>
        <div class="d-flex align-items-start">
            <div class="me-3 mt-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="var(--primary-color)" class="bi bi-chat-left-heart-fill" viewBox="0 0 16 16">
                  <path d="M2 0a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2.5a1 1 0 0 1 .8.4l1.9 2.533a1 1 0 0 0 1.6 0l1.9-2.533a1 1 0 0 1 .8-.4H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zm6 3.993c1.664-1.711 5.825 1.283 0 5.132-5.825-3.85-1.664-6.843 0-5.132"/>
                </svg>
            </div>
            <div>
                <h5 class="fw-bold mb-1">Communication Block Constraints</h5>
                <p class="text-secondary small mb-0">No unsolicited spam. You can only start messaging another user if you have both established an approved connection request (Interest).</p>
            </div>
        </div>
    </div>
    <div class="col-md-6 text-center">
        <img src="https://i.pinimg.com/1200x/52/ca/05/52ca05169f2dfce3b323d3bba3740019.jpg" class="img-fluid rounded-3 shadow-lg border" style="max-height: 440px; width: 100%; object-fit: cover;" alt="Authentic Nepalese Wedding Couple in Traditional Red Attire">
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
