<?php
require_once 'config.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'register':
        handleRegister();
        break;
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'check':
        handleCheck();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
}

function handleRegister() {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if (!$email || !$password) {
        echo json_encode(['success' => false, 'message' => 'Email dan password wajib diisi']);
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Format email tidak valid']);
        return;
    }

    if (strlen($password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password minimal 8 karakter']);
        return;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email sudah terdaftar']);
        return;
    }

    $nomorPendaftaran = generateNomorPendaftaran();
    // Pastikan unik
    while (true) {
        $stmt2 = $db->prepare("SELECT id FROM users WHERE nomor_pendaftaran = ?");
        $stmt2->bind_param('s', $nomorPendaftaran);
        $stmt2->execute();
        if ($stmt2->get_result()->num_rows === 0) break;
        $nomorPendaftaran = generateNomorPendaftaran();
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt3 = $db->prepare("INSERT INTO users (nomor_pendaftaran, email, password, role) VALUES (?, ?, ?, 'applicant')");
    $stmt3->bind_param('sss', $nomorPendaftaran, $email, $hashedPassword);

    if ($stmt3->execute()) {
        $userId = $db->insert_id;
        $stmt4 = $db->prepare("INSERT INTO applicants (user_id) VALUES (?)");
        $stmt4->bind_param('i', $userId);
        $stmt4->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Registrasi berhasil',
            'nomor_pendaftaran' => $nomorPendaftaran
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mendaftar, coba lagi']);
    }
    $db->close();
}

function handleLogin() {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if (!$email || !$password) {
        echo json_encode(['success' => false, 'message' => 'Email dan password wajib diisi']);
        return;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT u.*, a.id as applicant_id, a.nama_lengkap, a.status FROM users u LEFT JOIN applicants a ON u.id = a.user_id WHERE u.email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Email atau password salah']);
        return;
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['nomor_pendaftaran'] = $user['nomor_pendaftaran'];
    $_SESSION['applicant_id'] = $user['applicant_id'];
    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];

    echo json_encode([
        'success' => true,
        'role' => $user['role'],
        'nomor_pendaftaran' => $user['nomor_pendaftaran'],
        'nama_lengkap' => $user['nama_lengkap'],
        'status' => $user['status']
    ]);
    $db->close();
}

function handleLogout() {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logout berhasil']);
}

function handleCheck() {
    if (isLoggedIn()) {
        $db = getDB();
        $stmt = $db->prepare("SELECT a.*, u.email, u.nomor_pendaftaran, u.role FROM applicants a JOIN users u ON a.user_id = u.id WHERE u.id = ?");
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        echo json_encode([
            'success' => true,
            'loggedIn' => true,
            'role' => $_SESSION['role'],
            'data' => $data
        ]);
        $db->close();
    } else {
        echo json_encode(['success' => true, 'loggedIn' => false]);
    }
}
?>
