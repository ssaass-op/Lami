<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
function e(mixed $value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function verify_csrf_token(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}
function has_role(array|string $roles): bool {
    if (!is_logged_in()) {
        return false;
    }
    $user_role = $_SESSION['user_role'] ?? 'standard';
    if (is_array($roles)) {
        return in_array($user_role, $roles);
    }
    return $user_role === $roles;
}
function calculate_age(string $dob): int {
    $dob_date = new DateTime($dob);
    $today = new DateTime();
    $diff = $today->diff($dob_date);
    return $diff->y;
}
function upload_profile_file(array $file, string $upload_subfolder, array $allowed_types = ['image/jpeg', 'image/png', 'image/jpg']): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $file_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_type, $allowed_types)) {
        return null;
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return null;
    }
    $base_dir = dirname(__DIR__) . '/uploads/';
    $target_dir = $base_dir . $upload_subfolder . '/';
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('file_', true) . '.' . $extension;
    $target_file = $target_dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return 'uploads/' . $upload_subfolder . '/' . $filename;
    }
    return null;
}
function get_connection_status(int $user_a, int $user_b): ?string {
    $db = DB::getConnection();
    $stmt = $db->prepare("
        SELECT status FROM interests 
        WHERE (sender_id = :ua1 AND receiver_id = :ub1) 
           OR (sender_id = :ub2 AND receiver_id = :ua2)
    ");
    $stmt->execute([
        'ua1' => $user_a,
        'ub1' => $user_b,
        'ub2' => $user_b,
        'ua2' => $user_a
    ]);
    $result = $stmt->fetch();
    return $result ? $result['status'] : null;
}
function is_connected(int $user_a, int $user_b): bool {
    return get_connection_status($user_a, $user_b) === 'accepted';
}
function get_connection_details(int $user_a, int $user_b): ?array {
    $db = DB::getConnection();
    $stmt = $db->prepare("
        SELECT sender_id, status FROM interests 
        WHERE (sender_id = :ua1 AND receiver_id = :ub1) 
           OR (sender_id = :ub2 AND receiver_id = :ua2)
    ");
    $stmt->execute([
        'ua1' => $user_a,
        'ub1' => $user_b,
        'ub2' => $user_b,
        'ua2' => $user_a
    ]);
    return $stmt->fetch() ?: null;
}
function calculate_compatibility_score(array $prefs, array $target): int {
    $score = 0;
    $total_weight = 0;
    if (isset($prefs['min_age'], $prefs['max_age']) && isset($target['dob'])) {
        $age = calculate_age($target['dob']);
        $total_weight += 20;
        if ($age >= $prefs['min_age'] && $age <= $prefs['max_age']) {
            $score += 20;
        }
    }
    if (!empty($prefs['preferred_religion'])) {
        $total_weight += 15;
        if (isset($target['religion']) && strcasecmp($prefs['preferred_religion'], $target['religion']) === 0) {
            $score += 15;
        }
    }
    if (!empty($prefs['preferred_caste'])) {
        $total_weight += 15;
        if (isset($target['caste']) && strcasecmp($prefs['preferred_caste'], $target['caste']) === 0) {
            $score += 15;
        }
    }
    if (!empty($prefs['preferred_location'])) {
        $total_weight += 15;
        if (isset($target['location']) && strcasecmp($prefs['preferred_location'], $target['location']) === 0) {
            $score += 15;
        }
    }
    if (!empty($prefs['preferred_education'])) {
        $total_weight += 10;
        if (isset($target['education']) && stripos($target['education'], $prefs['preferred_education']) !== false) {
            $score += 10;
        }
    }
    if (!empty($prefs['preferred_marital_status']) && $prefs['preferred_marital_status'] !== 'any') {
        $total_weight += 10;
        if (isset($target['marital_status']) && $prefs['preferred_marital_status'] === $target['marital_status']) {
            $score += 10;
        }
    }
    if ((!empty($prefs['min_height']) || !empty($prefs['max_height'])) && !empty($target['height'])) {
        $total_weight += 5;
        $target_h = (float)$target['height'];
        $min_h = !empty($prefs['min_height']) ? (float)$prefs['min_height'] : 0;
        $max_h = !empty($prefs['max_height']) ? (float)$prefs['max_height'] : 300;
        if ($target_h >= $min_h && $target_h <= $max_h) {
            $score += 5;
        }
    }
    if (!empty($prefs['preferred_profession'])) {
        $total_weight += 5;
        if (isset($target['profession']) && stripos($target['profession'], $prefs['preferred_profession']) !== false) {
            $score += 5;
        }
    }
    if (!empty($prefs['preferred_income'])) {
        $total_weight += 5;
        if (isset($target['income_range']) && stripos($target['income_range'], $prefs['preferred_income']) !== false) {
            $score += 5;
        }
    }
    if ($total_weight === 0) {
        return 50;
    }
    return (int) round(($score / $total_weight) * 100);
}
