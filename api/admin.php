<?php
require_once 'config.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'dashboard':
        handleDashboard();
        break;
    case 'list':
        handleList();
        break;
    case 'detail':
        handleDetail();
        break;
    case 'update_status':
        handleUpdateStatus();
        break;
    case 'delete':
        handleDelete();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
}

function handleDashboard()
{
    requireAdmin();
    $db = getDB();

    $stats = [];
    $statuses = ['draft', 'pending', 'verified', 'accepted', 'rejected'];
    foreach ($statuses as $s) {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM applicants WHERE status = ?");
        $stmt->bind_param('s', $s);
        $stmt->execute();
        $stats[$s] = $stmt->get_result()->fetch_assoc()['count'];
    }

    $total = $db->query("SELECT COUNT(*) as count FROM applicants")->fetch_assoc()['count'];
    $stats['total'] = $total;

    // Recent applicants
    $recent = $db->query("SELECT a.nama_lengkap, a.status, a.updated_at, u.nomor_pendaftaran, u.email FROM applicants a JOIN users u ON a.user_id = u.id WHERE u.role = 'applicant' ORDER BY a.updated_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['success' => true, 'stats' => $stats, 'recent' => $recent]);
    $db->close();
}

function handleList()
{
    requireAdmin();
    $db = getDB();

    $status = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $where = "WHERE u.role = 'applicant'";
    $params = [];
    $types = '';

    if ($status) {
        $where .= " AND a.status = ?";
        $types .= 's';
        $params[] = $status;
    }
    if ($search) {
        $where .= " AND (a.nama_lengkap LIKE ? OR u.nomor_pendaftaran LIKE ? OR u.email LIKE ?)";
        $types .= 'sss';
        $s = "%$search%";
        $params[] = $s;
        $params[] = $s;
        $params[] = $s;
    }

    $countSql = "SELECT COUNT(*) as total FROM applicants a JOIN users u ON a.user_id = u.id $where";
    $stmt = $db->prepare($countSql);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];

    $types2 = $types . 'ii';
    $params2 = array_merge($params, [$limit, $offset]);

    $sql = "SELECT a.id, a.nama_lengkap, a.status, a.pilihan_prodi_1, a.pilihan_prodi_2, a.updated_at, u.nomor_pendaftaran, u.email, u.created_at FROM applicants a JOIN users u ON a.user_id = u.id $where ORDER BY a.updated_at DESC LIMIT ? OFFSET ?";
    $stmt2 = $db->prepare($sql);
    $stmt2->bind_param($types2, ...$params2);
    $stmt2->execute();
    $data = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $data,
        'total' => $total,
        'page' => $page,
        'pages' => ceil($total / $limit)
    ]);
    $db->close();
}

function handleDetail()
{
    requireAdmin();
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
        return;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT a.*, u.email, u.nomor_pendaftaran, u.created_at as tgl_daftar FROM applicants a JOIN users u ON a.user_id = u.id WHERE a.id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();

    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
        return;
    }

    // Tambahkan URL lengkap untuk foto dan ijazah
    $data['foto_url']   = !empty($data['foto'])   ? UPLOAD_URL . $data['foto']   : null;
    $data['ijazah_url'] = !empty($data['ijazah']) ? UPLOAD_URL . $data['ijazah'] : null;

    echo json_encode(['success' => true, 'data' => $data]);
    $db->close();
}

function handleUpdateStatus()
{
    requireAdmin();
    $data = json_decode(file_get_contents('php://input'), true);
    $id = intval($data['id'] ?? 0);
    $status = $data['status'] ?? '';
    $catatan = $data['catatan'] ?? '';

    $valid = ['pending', 'verified', 'accepted', 'rejected'];
    if (!$id || !in_array($status, $valid)) {
        echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
        return;
    }

    $db = getDB();
    $stmt = $db->prepare("UPDATE applicants SET status = ?, catatan_admin = ? WHERE id = ?");
    $stmt->bind_param('ssi', $status, $catatan, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Status berhasil diupdate']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal update status']);
    }
    $db->close();
}

function handleDelete()
{
    requireAdmin();
    $data = json_decode(file_get_contents('php://input'), true);
    $id = intval($data['id'] ?? 0);

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
        return;
    }

    $db = getDB();
    // Ambil user_id dulu
    $stmt = $db->prepare("SELECT user_id FROM applicants WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
        return;
    }

    $stmt2 = $db->prepare("DELETE FROM users WHERE id = ? AND role = 'applicant'");
    $stmt2->bind_param('i', $row['user_id']);

    if ($stmt2->execute()) {
        echo json_encode(['success' => true, 'message' => 'Data pendaftar berhasil dihapus']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus data']);
    }
    $db->close();
}
