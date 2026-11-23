<?php
if (!defined('BASE_URL')) {
    $project_root = str_replace('\\', '/', realpath(dirname(__DIR__)));
    $script_filename = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    $relative_path = '';
    if ($project_root && $script_filename && strpos($script_filename, $project_root) === 0) {
        $relative_path = ltrim(substr($script_filename, strlen($project_root)), '/');
    }
    $web_root = '/';
    if (!empty($relative_path) && !empty($script_name)) {
        if (substr($script_name, -strlen($relative_path)) === $relative_path) {
            $web_root = substr($script_name, 0, -strlen($relative_path));
        }
    }
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL', $protocol . '://' . $host . $web_root);
}
class DB {
    private static ?PDO $instance = null;
    private function __construct() {}
    private function __clone() {}
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: 'localhost';
            $db   = getenv('DB_NAME') ?: 'lami_db';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
            $port = getenv('DB_PORT') ?: '3306';
            $charset = 'utf8mb4';
            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                die("Database Connection Failed: " . htmlspecialchars($e->getMessage()));
            }
        }
        return self::$instance;
    }
}
