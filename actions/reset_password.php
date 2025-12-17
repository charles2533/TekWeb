<?php
require 'includes/db.php';

$token = $_GET['token'] ?? '';
$error = '';

// Validasi Token
if ($token) {
    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires_at > ?");
    $stmt->execute([$token, $now]);
    $user = $stmt->fetch();

    if (!$user) {
        die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h3>Link tidak valid atau sudah kadaluarsa.</h3><a href='forgot_password.php'>Ulangi Request</a></div>");
    }
} else {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
</head>
<body class="bg-[#0F1014] text-white min-h-screen flex items-center justify-center font-[Inter]">
    
    <div class="w-full max-w-md px-6">
        <div class="bg-[#18181B] border border-white/10 p-8 rounded-2xl shadow-xl">
            <h1 class="text-2xl font-bold text-white mb-2">Reset Password</h1>
            <p class="text-slate-400 text-sm mb-6">Masukkan password baru untuk akun Anda.</p>

            <form action="actions/process_reset.php" method="POST" class="space-y-5">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Password Baru</label>
                    <input type="password" name="password" required class="w-full bg-[#27272A] border border-slate-600 text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 outline-none">
                </div>
                
                <button type="submit" class="w-full bg-amber-500 hover:bg-amber-400 text-black font-bold py-3 rounded-lg transition">
                    SIMPAN PASSWORD BARU
                </button>
            </form>
        </div>
    </div>

</body>
</html>