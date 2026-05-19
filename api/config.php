<?php
// config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'itb_pmb');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
// Auto-detect BASE_URL agar bisa dipakai di environment apapun
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script   = $_SERVER['SCRIPT_NAME'] ?? '';
// Ambil base path sampai folder itb-pmb (dua level atas dari /api/file.php)
$basePath = rtrim(dirname(dirname($script)), '/\\');
define('BASE_URL',    $protocol . '://' . $host . $basePath);
define('UPLOAD_URL',  BASE_URL . '/api/uploads/');

function getDB()
{
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die(json_encode(['success' => false, 'message' => 'Koneksi database gagal: ' . $conn->connect_error]));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

session_start();

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin()
{
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Harap login terlebih dahulu.']);
        exit;
    }
}

function requireAdmin()
{
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden. Akses ditolak.']);
        exit;
    }
}

function generateNomorPendaftaran()
{
    return 'ITB' . date('Y') . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
