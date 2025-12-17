<?php
// File: actions/update_event.php

session_start();
require '../includes/db.php';

// 1. CEK KEAMANAN: Hanya Admin yang boleh akses
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    die("AKSES DITOLAK: Anda bukan Admin.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2. AMBIL DATA dari form edit
    $event_id   = $_POST['event_id'];
    $name       = $_POST['name'];
    $place      = $_POST['place'];
    $date       = $_POST['date'];
    $time_start = $_POST['time_start'];
    $time_end   = $_POST['time_end'];

    try {
        // 3. QUERY UPDATE ke database
        $sql = "UPDATE events SET 
                name = :name, 
                place = :place, 
                date = :date, 
                time_start = :time_start, 
                time_end = :time_end 
                WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        
        $stmt->execute([
            'name'       => $name,
            'place'      => $place,
            'date'       => $date,
            'time_start' => $time_start,
            'time_end'   => $time_end,
            'id'         => $event_id
        ]);

        // 4. SUKSES: Redirect kembali ke dashboard
        header("Location: ../dashboard.php?msg=Event berhasil diupdate");
        exit;

    } catch (PDOException $e) {
        die("Gagal mengupdate event: " . $e->getMessage());
    }
} else {
    // Jika file dibuka paksa tanpa lewat form
    header("Location: ../dashboard.php");
    exit;
}
?>