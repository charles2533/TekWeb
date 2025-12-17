<?php
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    die("Akses Ditolak");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $event_id = $_POST['event_id'];

    try {
        $stmt = $conn->prepare("DELETE FROM attendances WHERE user_id = :uid AND event_id = :eid");
        $stmt->execute(['uid' => $user_id, 'eid' => $event_id]);

        header("Location: ../dashboard.php?status=cancelled");
        exit;

    } catch (PDOException $e) {
        die("Gagal membatalkan: " . $e->getMessage());
    }
}
?>