<?php
$host = 'localhost';
$db_name = 'tekweb_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

} catch(PDOException $e) {

    die("Koneksi Database Gagal: " . $e->getMessage());
}
?>