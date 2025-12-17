<?php
session_start(); // Penting: Mulai session untuk kirim pesan flash
require '../includes/db.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    die("AKSES DITOLAK");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'];

    try {
        // Hapus event dari database
        $stmt = $conn->prepare("DELETE FROM events WHERE id = :id");
        $stmt->execute(['id' => $event_id]);

        // 1. Simpan pesan sukses ke session (agar muncul SweetAlert hijau di dashboard)
        $_SESSION['flash_success'] = "Event berhasil dihapus permanen.";

        // 2. Redirect (Lempar balik) ke dashboard
        header("Location: ../dashboard.php");
        exit;

    } catch (PDOException $e) {
        // Jika gagal, simpan pesan error
        $_SESSION['flash_error'] = "Gagal menghapus event: " . $e->getMessage();
        header("Location: ../dashboard.php");
        exit;
    }
} else {
    // Jika file dibuka paksa tanpa lewat form delete
    header("Location: ../dashboard.php");
    exit;
}
?>