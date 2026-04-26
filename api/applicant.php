<?php
require_once 'config.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        handleGet();
        break;
    case 'update':
        handleUpdate();
        break;
    case 'upload':
        handleUpload();
        break;
    case 'submit':
        handleSubmit();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
}

function handleGet() {
    requireLogin();
    $db = getDB();
    $stmt = $db->prepare("SELECT a.*, u.email, u.nomor_pendaftaran FROM applicants a JOIN users u ON a.user_id = u.id WHERE u.id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();

    // Tambahkan URL lengkap untuk foto dan ijazah
    if ($data) {
        $data['foto_url']   = !empty($data['foto'])   ? UPLOAD_URL . $data['foto']   : null;
        $data['ijazah_url'] = !empty($data['ijazah']) ? UPLOAD_URL . $data['ijazah'] : null;
    }

    echo json_encode(['success' => true, 'data' => $data]);
    $db->close();
}

function handleUpdate() {
    requireLogin();
    $data = json_decode(file_get_contents('php://input'), true);

    // Cek apakah status masih bisa diedit (draft atau pending = belum diverifikasi)
    $db = getDB();
    $stmt = $db->prepare("SELECT status FROM applicants WHERE user_id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row && in_array($row['status'], ['verified', 'accepted', 'rejected'])) {
        echo json_encode(['success' => false, 'message' => 'Data tidak dapat diubah setelah diverifikasi admin']);
        return;
    }

    $fields = ['nama_lengkap', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'alamat', 'kota', 'provinsi', 'kode_pos', 'no_telepon', 'asal_sekolah', 'jurusan_sekolah', 'tahun_lulus', 'pilihan_prodi_1', 'pilihan_prodi_2', 'jalur_seleksi'];
    $updates = [];
    $types = '';
    $values = [];

    foreach ($fields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $types .= 's';
            $values[] = $data[$field];
        }
    }

    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'Tidak ada data yang diupdate']);
        return;
    }

    $types .= 'i';
    $values[] = $_SESSION['user_id'];

    $sql = "UPDATE applicants SET " . implode(', ', $updates) . " WHERE user_id = ?";
    $stmt2 = $db->prepare($sql);
    $stmt2->bind_param($types, ...$values);

    if ($stmt2->execute()) {
        echo json_encode(['success' => true, 'message' => 'Data berhasil disimpan']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data']);
    }
    $db->close();
}

function handleUpload() {
    requireLogin();

    $type = $_POST['type'] ?? '';
    if (!in_array($type, ['foto', 'ijazah'])) {
        echo json_encode(['success' => false, 'message' => 'Tipe file tidak valid']);
        return;
    }

    if (!isset($_FILES['file'])) {
        echo json_encode(['success' => false, 'message' => 'File tidak ditemukan']);
        return;
    }

    // Cek status
    $db = getDB();
    $stmt = $db->prepare("SELECT status FROM applicants WHERE user_id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row && in_array($row['status'], ['verified', 'accepted', 'rejected'])) {
        echo json_encode(['success' => false, 'message' => 'File tidak dapat diubah setelah diverifikasi']);
        return;
    }

    $file = $_FILES['file'];
    $allowed_foto = ['image/jpeg', 'image/png', 'image/jpg'];
    $allowed_ijazah = ['image/jpeg', 'image/png', 'application/pdf'];

    $allowed = $type === 'foto' ? $allowed_foto : $allowed_ijazah;
    if (!in_array($file['type'], $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Format file tidak didukung']);
        return;
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 2MB']);
        return;
    }

    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $_SESSION['user_id'] . '_' . $type . '_' . time() . '.' . $ext;
    $filepath = UPLOAD_DIR . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $stmt2 = $db->prepare("UPDATE applicants SET $type = ? WHERE user_id = ?");
        $stmt2->bind_param('si', $filename, $_SESSION['user_id']);
        $stmt2->execute();
        echo json_encode([
            'success'    => true,
            'filename'   => $filename,
            'upload_url' => UPLOAD_URL . $filename
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengupload file']);
    }
    $db->close();
}

function handleSubmit() {
    requireLogin();
    $db = getDB();

    $stmt = $db->prepare("SELECT * FROM applicants WHERE user_id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $applicant = $stmt->get_result()->fetch_assoc();

    $required = ['nama_lengkap', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'alamat', 'kota', 'provinsi', 'no_telepon', 'asal_sekolah', 'pilihan_prodi_1'];
    foreach ($required as $field) {
        if (empty($applicant[$field])) {
            echo json_encode(['success' => false, 'message' => "Field '$field' belum diisi"]);
            return;
        }
    }

    if (in_array($applicant['status'], ['pending', 'verified', 'accepted', 'rejected'])) {
        echo json_encode(['success' => false, 'message' => 'Pendaftaran sudah disubmit sebelumnya']);
        return;
    }

    $stmt2 = $db->prepare("UPDATE applicants SET status = 'pending' WHERE user_id = ?");
    $stmt2->bind_param('i', $_SESSION['user_id']);
    $stmt2->execute();

    echo json_encode(['success' => true, 'message' => 'Pendaftaran berhasil disubmit, menunggu verifikasi admin']);
    $db->close();
}
?>
