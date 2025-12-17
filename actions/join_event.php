<?php
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    die("Silakan login terlebih dahulu.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $event_id = $_POST['event_id'];
    $role = $_POST['role']; 

    try {
        $check = $conn->prepare("SELECT * FROM attendances WHERE user_id = ? AND event_id = ?");
        $check->execute([$user_id, $event_id]);
        
        if ($check->rowCount() > 0) {
            header("Location: ../dashboard.php?msg=Anda sudah terdaftar di event ini!");
            exit;
        }

        $evtStmt = $conn->prepare("SELECT req_operator, req_cameraman FROM events WHERE id = ?");
        $evtStmt->execute([$event_id]);
        $event = $evtStmt->fetch();

        $countStmt = $conn->prepare("SELECT COUNT(*) FROM attendances WHERE event_id = ? AND role = ?");
        $countStmt->execute([$event_id, $role]);
        $currentCount = $countStmt->fetchColumn();

        $limit = ($role === 'Operator') ? $event['req_operator'] : $event['req_cameraman'];

        if ($currentCount >= $limit) {
            header("Location: ../dashboard.php?msg=Slot untuk $role sudah penuh!");
            exit;
        }

        $insert = $conn->prepare("INSERT INTO attendances (user_id, event_id, role) VALUES (?, ?, ?)");
        $insert->execute([$user_id, $event_id, $role]);

        header("Location: ../dashboard.php?status=joined");
        exit;

    } catch (PDOException $e) {
        die("Gagal join event: " . $e->getMessage());
    }
}
?>