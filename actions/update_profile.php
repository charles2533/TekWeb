<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'];

try {
    if ($action === 'update_info') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);

        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->execute([$name, $email, $user_id]);

        $_SESSION['user_name'] = $name;

        header("Location: ../profile.php?status=success");
        exit;
    } 
    
    elseif ($action === 'update_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($current_password !== $user['password']) {
            header("Location: ../profile.php?status=wrong_password");
            exit;
        }

        if ($new_password !== $confirm_password) {
            header("Location: ../profile.php?status=password_mismatch");
            exit;
        }

        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$new_password, $user_id]);

        header("Location: ../profile.php?status=success");
        exit;
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>