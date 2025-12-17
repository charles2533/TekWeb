<?php
// Simpan di folder: actions/
// Nama file: admin_remove_attendee.php

session_start();
require '../includes/db.php';

// 1. CEK KEAMANAN: Hanya Admin yang boleh akses
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    die("AKSES DITOLAK: Anda bukan Admin.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2. AMBIL DATA: ID Event dan ID Peserta yang mau dihapus
    $event_id = $_POST['event_id'];
    $user_id_target = $_POST['user_id']; 

    try {
        // 3. QUERY HAPUS: Hapus dari tabel absensi (attendances)
        // Logikanya: "Hapus kehadiran dimana Event-nya X dan User-nya Y"
        $stmt = $conn->prepare("DELETE FROM attendances WHERE event_id = :eid AND user_id = :uid");
        
        $stmt->execute([
            'eid' => $event_id,
            'uid' => $user_id_target
        ]);

        // 4. SUKSES: Redirect kembali ke dashboard
        header("Location: ../dashboard.php?msg=Peserta berhasil dihapus");
        exit;

    } catch (PDOException $e) {
        // Jika error database
        die("Gagal menghapus data: " . $e->getMessage());
    }
} else {
    // Jika ada yang coba buka file ini tanpa lewat tombol delete (GET request)
    header("Location: ../dashboard.php");
    exit;
}
?>