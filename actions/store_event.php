<?php
require '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    die("AKSES DITOLAK: Kamu bukan admin.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $place = $_POST['place'];
    $date = $_POST['date'];
    $time_start = $_POST['time_start'];
    $time_end = $_POST['time_end'];
    $req_operator = !empty($_POST['req_operator']) ? $_POST['req_operator'] : 1;
    $req_cameraman = !empty($_POST['req_cameraman']) ? $_POST['req_cameraman'] : 1;

    try {
        
        $sql = "INSERT INTO events (name, place, date, time_start, time_end, req_operator, req_cameraman) 
                VALUES (:name, :place, :date, :start, :end, :op, :cam)";
        
        $stmt = $conn->prepare($sql);
        
        $stmt->execute([
            'name' => $name,
            'place' => $place,
            'date' => $date,
            'end' => $time_end,
            'start' => $time_start,
            'op' => $req_operator,
            'cam' => $req_cameraman
        ]);

        header("Location: ../dashboard.php?status=created");
        exit;

    } catch (PDOException $e) {
        die("Gagal menyimpan event: " . $e->getMessage());
    }
}
?>