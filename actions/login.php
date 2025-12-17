<?php

require '../includes/db.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['is_admin'] = $user['is_admin'];

        
        header("Location: ../dashboard.php");
        exit;

    } else {
        
        header("Location: ../index.php?error=Email atau Password salah!&email=" . urlencode($email));
        exit;
    }
} else {

    header("Location: ../index.php");
    exit;
}
?>