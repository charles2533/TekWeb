<?php
session_start();
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    try {
        $stmt = $conn->prepare("DELETE FROM attendances WHERE user_id = ?");
        $stmt->execute([$user_id]);

        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);

        session_destroy();
        header("Location: ../index.php");
        exit;

    } catch (PDOException $e) {
        die("Gagal menghapus akun: " . $e->getMessage());
    }
}
?>