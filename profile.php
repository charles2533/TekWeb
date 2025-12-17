<?php
session_start();
require 'includes/db.php'; // Pastikan file ini ada dan koneksi $conn aktif

// 1. Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$is_admin = $_SESSION['is_admin'] ?? false; 

$message = null; 
$msg_type = ''; // 'success' atau 'error'

// --- LOGIKA PEMROSESAN FORM (Server Side) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // A. UPDATE INFO
    if ($action === 'update_info') {
        // Gunakan trim() untuk menghapus spasi di awal/akhir
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        
        // Validasi PHP (Server side validation)
        if(empty($name) || empty($email)) {
             $message = "Nama dan Email tidak boleh kosong.";
             $msg_type = 'error';
        } else {
            try {
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                $stmt->execute([$name, $email, $user_id]);
                
                // Update session agar nama di navbar langsung berubah
                $_SESSION['user_name'] = $name; 
                
                $message = "Profil berhasil diperbarui!";
                $msg_type = 'success';
            } catch (Exception $e) {
                $message = "Gagal update: " . $e->getMessage();
                $msg_type = 'error';
            }
        }
    }
    // B. UPDATE PASSWORD
    elseif ($action === 'update_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
             $message = "Semua kolom password wajib diisi.";
             $msg_type = 'error';
        } elseif ($new_password !== $confirm_password) {
            $message = "Password baru dan konfirmasi tidak cocok.";
            $msg_type = 'error';
        } else {
            // Ambil password lama dari DB untuk verifikasi
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user_data && password_verify($current_password, $user_data['password'])) {
                // Hash password baru sebelum simpan
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update->execute([$new_hash, $user_id]);
                
                $message = "Password berhasil diubah!";
                $msg_type = 'success';
            } else {
                $message = "Password saat ini salah.";
                $msg_type = 'error';
            }
        }
    }
}

// --- AMBIL DATA USER TERBARU ---
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $user = [];
}

$email = $user['email'] ?? ''; 
$name = $user['name'] ?? '';

// ==========================================
// KONFIGURASI TEMA
// ==========================================
if ($is_admin) {
    // TEMA ADMIN (PUTIH)
    $t_body     = "bg-slate-50 text-slate-800";
    $t_nav      = "bg-white border-b border-slate-200";
    $t_card     = "bg-white border border-slate-200 shadow-sm";
    $t_head     = "text-slate-900";
    $t_subhead  = "text-slate-500";
    $t_label    = "text-slate-500";
    $t_input    = "bg-white border border-slate-300 text-slate-900 focus:ring-blue-600 placeholder-slate-400";
    $t_btn      = "bg-blue-900 text-white hover:bg-blue-800 shadow-blue-900/20";
    $t_icon     = "text-blue-600";
} else {
    // TEMA USER (HITAM)
    $t_body     = "bg-[#0F1014] text-slate-300";
    $t_nav      = "bg-[#18181B] border-b border-white/5";
    $t_card     = "bg-[#18181B] border border-white/5 shadow-xl";
    $t_head     = "text-white";
    $t_subhead  = "text-slate-500";
    $t_label    = "text-slate-500";
    $t_input    = "bg-[#0F1014] border border-white/10 text-white focus:ring-amber-500 placeholder-slate-700";
    $t_btn      = "bg-white text-black hover:bg-slate-200 shadow-white/10";
    $t_icon     = "text-amber-500";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profile Settings - GKI Scheduler</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="antialiased <?= $t_body ?>">

    <nav class="<?= $t_nav ?> p-4 sticky top-0 z-50 transition-colors">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <a href="dashboard.php">
                    <img src="assets/image/GKI_logo.png" 
                         alt="GKI Logo" 
                         class="h-12 w-auto object-contain hover:opacity-80 transition-opacity">
                </a>
            </div>

            <div class="flex items-center gap-4">
                <a href="dashboard.php" class="text-sm font-bold opacity-60 hover:opacity-100 transition">Dashboard</a>
                <div class="h-4 w-[1px] bg-current opacity-20"></div>
                <div class="font-bold opacity-90"><?= htmlspecialchars($name) ?></div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-12 space-y-8">
        
        <div class="flex items-center gap-3 mb-8">
            <h1 class="text-2xl font-bold flex items-center gap-2 <?= $t_head ?>">
                <span class="<?= $t_icon ?>">✦</span> Profile Settings
            </h1>
        </div>

        <div class="<?= $t_card ?> rounded-2xl p-8 transition-colors">
            <div class="mb-6">
                <h2 class="text-lg font-bold <?= $t_head ?>">Profile Information</h2>
                <p class="<?= $t_subhead ?> text-sm mt-1">Update your account's profile information and email address.</p>
            </div>

            <form id="infoForm" action="" method="POST" class="space-y-6" novalidate>
                <input type="hidden" name="action" value="update_info">
                
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-2 <?= $t_label ?>">NAME</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" 
                           class="w-full rounded-lg px-4 py-3 focus:ring-2 focus:border-transparent outline-none transition font-medium <?= $t_input ?>">
                    
                    <p id="name_error" class="hidden text-rose-500 text-xs mt-2 font-bold flex items-center">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Nama wajib diisi.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-2 <?= $t_label ?>">EMAIL</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" 
                           class="w-full rounded-lg px-4 py-3 focus:ring-2 focus:border-transparent outline-none transition font-medium <?= $t_input ?>">
                    
                    <p id="email_error" class="hidden text-rose-500 text-xs mt-2 font-bold flex items-center">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Format email tidak valid atau kosong.
                    </p>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2 rounded-lg font-bold text-sm transition shadow-lg <?= $t_btn ?>">
                        SAVE
                    </button>
                </div>
            </form>
        </div>

        <div class="<?= $t_card ?> rounded-2xl p-8 transition-colors">
            <div class="mb-6">
                <h2 class="text-lg font-bold <?= $t_head ?>">Update Password</h2>
                <p class="<?= $t_subhead ?> text-sm mt-1">Ensure your account is using a long, random password to stay secure.</p>
            </div>

            <form id="passwordForm" action="" method="POST" class="space-y-6" novalidate>
                <input type="hidden" name="action" value="update_password">

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-2 <?= $t_label ?>">CURRENT PASSWORD</label>
                    <input type="password" id="current_password" name="current_password" placeholder="••••••••"
                           class="w-full rounded-lg px-4 py-3 focus:ring-2 focus:border-transparent outline-none transition font-medium <?= $t_input ?>">
                    
                    <p id="current_password_error" class="hidden text-rose-500 text-xs mt-2 font-bold flex items-center">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Password saat ini wajib diisi.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-2 <?= $t_label ?>">NEW PASSWORD</label>
                    <input type="password" id="new_password" name="new_password" placeholder="••••••••"
                           class="w-full rounded-lg px-4 py-3 focus:ring-2 focus:border-transparent outline-none transition font-medium <?= $t_input ?>">
                    
                    <p id="new_password_error" class="hidden text-rose-500 text-xs mt-2 font-bold flex items-center">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Password baru wajib diisi.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-2 <?= $t_label ?>">CONFIRM PASSWORD</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••"
                           class="w-full rounded-lg px-4 py-3 focus:ring-2 focus:border-transparent outline-none transition font-medium <?= $t_input ?>">
                    
                    <p id="confirm_password_error" class="hidden text-rose-500 text-xs mt-2 font-bold flex items-center">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Konfirmasi password tidak sesuai.
                    </p>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2 rounded-lg font-bold text-sm transition shadow-lg <?= $t_btn ?>">
                        SAVE
                    </button>
                </div>
            </form>
        </div>

        <div class="<?= $t_card ?> rounded-2xl p-8 relative overflow-hidden transition-colors">
            <div class="flex justify-between items-start relative z-10">
                <div class="max-w-xl">
                    <h2 class="text-lg font-bold <?= $t_head ?>">Delete Account</h2>
                    <p class="<?= $t_subhead ?> text-sm mt-1">Once your account is deleted, all of its resources and data will be permanently deleted.</p>
                </div>
                <form id="deleteForm" action="actions/delete_account.php" method="POST">
                    <button type="button" onclick="confirmDelete()" class="bg-rose-600 hover:bg-rose-500 text-white px-6 py-2 rounded-lg font-bold text-sm transition shadow-lg shadow-rose-600/20">
                        DELETE ACCOUNT
                    </button>
                </form>
            </div>
        </div>

    </div>

    <script>
        // ==========================================
        // 1. VALIDASI FORM (CLIENT SIDE)
        // ==========================================
        
        function toggleError(inputId, isError, messageElementId) {
            const input = document.getElementById(inputId);
            const errorMsg = document.getElementById(messageElementId);
            
            if (isError) {
                input.classList.add('border-rose-500', 'ring-1', 'ring-rose-500'); 
                errorMsg.classList.remove('hidden');
            } else {
                input.classList.remove('border-rose-500', 'ring-1', 'ring-rose-500');
                errorMsg.classList.add('hidden');
            }
        }

        // --- VALIDASI INFO FORM ---
        document.getElementById('infoForm').addEventListener('submit', function(e) {
            let isValid = true;
            
            // Cek Nama
            const nameVal = document.getElementById('name').value.trim();
            if (!nameVal) {
                toggleError('name', true, 'name_error');
                isValid = false;
            }

            // Cek Email
            const emailVal = document.getElementById('email').value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailVal || !emailRegex.test(emailVal)) {
                toggleError('email', true, 'email_error');
                isValid = false;
            }

            if (!isValid) e.preventDefault();
        });

        // --- VALIDASI PASSWORD FORM ---
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            let isValid = true;
            
            const currentPass = document.getElementById('current_password').value;
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;

            if (!currentPass) {
                toggleError('current_password', true, 'current_password_error');
                isValid = false;
            }

            if (!newPass) {
                toggleError('new_password', true, 'new_password_error');
                isValid = false;
            }

            if (!confirmPass || confirmPass !== newPass) {
                toggleError('confirm_password', true, 'confirm_password_error');
                isValid = false;
            }

            if (!isValid) e.preventDefault();
        });

        // Hapus Error Saat Mengetik (Live Feedback)
        ['name', 'email', 'current_password', 'new_password', 'confirm_password'].forEach(id => {
            const el = document.getElementById(id);
            if(el){
                el.addEventListener('input', function() {
                    toggleError(id, false, id + '_error');
                });
            }
        });


        // ==========================================
        // 2. ALERT & KONFIRMASI (SWEETALERT)
        // ==========================================
        
        // Menampilkan pesan sukses/gagal dari PHP menggunakan SweetAlert
        <?php if ($message): ?>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: '<?= $msg_type === 'success' ? 'Berhasil!' : 'Gagal!' ?>',
                    text: '<?= addslashes($message) ?>',
                    icon: '<?= $msg_type ?>',
                    background: '<?= $is_admin ? "#ffffff" : "#18181B" ?>',
                    color: '<?= $is_admin ? "#1e293b" : "#e2e8f0" ?>',
                    confirmButtonColor: '<?= $is_admin ? "#1e3a8a" : "#f59e0b" ?>',
                    confirmButtonText: 'OK'
                });
            });
        <?php endif; ?>

        // Konfirmasi Hapus Akun
        function confirmDelete() {
            Swal.fire({
                title: 'Apakah Anda Yakin?',
                text: "Akun akan dihapus permanen dan tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                background: '<?= $is_admin ? "#ffffff" : "#18181B" ?>',
                color: '<?= $is_admin ? "#1e293b" : "#e2e8f0" ?>',
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('deleteForm').submit();
                }
            })
        }
    </script>

</body>
</html>