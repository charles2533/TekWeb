<?php

require_once '../includes/db.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    
    
    global $conn; 
    
    try {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
          
            $token = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

          
            $insert = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $insert->execute([$email, $token, $expires]);
            
           
            header("Location: forgot_password.php?status=success");
            exit();
        } else {
        
            header("Location: forgot_password.php?error=notfound");
            exit();
        }
    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
}
?>