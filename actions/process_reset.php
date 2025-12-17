<?php
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $password = $_POST['password']; // Ingat: Gunakan password_hash() jika sistem Anda sudah memakainya.

    // Cek Token Lagi (Double check)
    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires_at > ?");
    $stmt->execute([$token, $now]);
    $user = $stmt->fetch();

    if ($user) {
        // Update Password & Hapus Token
        // $hashed = password_hash($password, PASSWORD_DEFAULT); // Uncomment jika pakai hash
        
        $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ?");
        $update->execute([$password, $user['id']]); // Ganti $password dengan $hashed jika pakai hash

        // Redirect ke Login dengan pesan sukses
        header("Location: ../index.php?reset=success");
        exit;
    } else {
        echo "Token invalid.";
    }
}
?>