<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Church Scheduler</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="font-sans text-gray-900 antialiased relative">
    
    <div class="min-h-screen w-full bg-cover bg-center relative"
         style="background-image: url('assets/image/backgroundGereja.png');">
        
        <div class="absolute inset-0 bg-black/70"></div>

        <div class="relative z-0 h-full min-h-screen flex items-center justify-center">
            
            <div class="absolute top-6 left-6 z-20">
                <a href="index.php">
                    <img src="assets/image/GKI_logo.png" alt="Logo GKI" 
                         class="h-18 w-auto drop-shadow-lg hover:scale-105 transition duration-300">
                </a>
            </div>

            <div class="container mx-auto px-6 lg:px-20 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center h-full w-full">
                
                <div class="text-white hidden lg:flex flex-col gap-4">
                    <h1 class="text-6xl font-bold leading-none">
                        Welcome to
                    </h1>

                    <h2 class="text-4xl font-semibold tracking-wide">
                        Church Scheduler
                    </h2>

                    <p class="text-gray-200 text-lg leading-relaxed mt-2 opacity-90">
                        Sistem Penjadwalan Pelayanan Gereja.<br>
                        Melayani dengan sepenuh hati.
                    </p>

                    <div class="flex gap-4 pt-4">
                        <a href="https://www.youtube.com/@GKIJEMURSARI" class="bg-white/20 hover:bg-white/30 p-3 rounded-full transition backdrop-blur-sm">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                        </a>

                        <a href="https://www.instagram.com/gkijemursari" class="bg-white/20 hover:bg-white/30 p-3 rounded-full transition backdrop-blur-sm">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        </a>
                    </div>
                </div>

                <div class="w-full max-w-md mx-auto ml-auto">
                    
                    <h2 class="text-3xl font-bold text-white mb-8 text-left lg:text-left text-center">Sign in</h2>
                    
                    <?php if(isset($_GET['error'])): ?>
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <strong class="font-bold">Error!</strong>
                            <span class="block sm:inline"><?php echo htmlspecialchars($_GET['error']); ?></span>
                        </div>
                    <?php endif; ?>

                    <form id="loginForm" method="POST" action="actions/login.php" class="space-y-5" novalidate>
    
    <div>
        <label for="email" class="block text-sm font-medium text-white mb-1 pl-1">Email Address</label>
        <input id="email" type="email" name="email" 
               class="w-full px-4 py-3 rounded-lg bg-white border-2 border-transparent focus:ring-2 focus:ring-amber-400 text-gray-900 placeholder-gray-400 transition-colors"
               placeholder="Masukan email..." 
               value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>" />
        
        <p id="email_error" class="hidden text-red-300 text-sm mt-1 font-medium flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Email wajib diisi.
        </p>
    </div>

    <div>
        <label for="password" class="block text-sm font-medium text-white mb-1 pl-1">Password</label>
        <input id="password" type="password" name="password" 
               class="w-full px-4 py-3 rounded-lg bg-white border-2 border-transparent focus:ring-2 focus:ring-amber-400 text-gray-900 placeholder-gray-400 transition-colors"
               placeholder="Masukan password..." />
               
        <p id="password_error" class="hidden text-red-300 text-sm mt-1 font-medium flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Password wajib diisi.
        </p>
    </div>

    <div class="flex items-center justify-between text-sm">
        <label for="remember_me" class="inline-flex items-center text-white cursor-pointer select-none">
            <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-amber-500 shadow-sm focus:ring-amber-500" name="remember">
            <span class="ml-2">Remember Me</span>
        </label>

        <a class="text-amber-300 hover:text-amber-200 font-medium hover:underline transition duration-200" 
           href="actions/forgot_password.php">
            Forgot your password?
        </a>
    </div>

    <button type="submit" 
            class="w-full py-3 px-4 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-lg shadow-lg transition duration-200 uppercase tracking-wide transform hover:-translate-y-0.5">
        Sign in
    </button>

    <div class="pt-6 mt-4 border-t border-white/10 text-xs text-gray-300 text-center lg:text-left">
        &copy; <?php echo date('Y'); ?> Church Scheduler. <br>
        <div class="mt-1 space-x-2">
            <a href="#" class="hover:text-white hover:underline">Terms of Service</a>
            <span>&bull;</span>
            <a href="#" class="hover:text-white hover:underline">Privacy Policy</a>
        </div>
    </div>
</form>

<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    // Ambil elemen input dan pesan error
    const emailInput = document.getElementById('email');
    const passInput = document.getElementById('password');
    const emailError = document.getElementById('email_error');
    const passError = document.getElementById('password_error');
    
    let isValid = true;

    // Reset style error sebelumnya (Hapus border merah dan sembunyikan pesan)
    [emailInput, passInput].forEach(input => {
        input.classList.remove('border-red-500', 'bg-red-50');
        input.classList.add('border-transparent');
    });
    [emailError, passError].forEach(el => el.classList.add('hidden'));

    // Validasi Email
    if (!emailInput.value.trim()) {
        e.preventDefault(); // Mencegah form submit
        emailInput.classList.remove('border-transparent');
        emailInput.classList.add('border-red-500', 'bg-red-50'); // Tambah border merah
        emailError.classList.remove('hidden'); // Munculkan pesan text
        isValid = false;
    }

    // Validasi Password
    if (!passInput.value.trim()) {
        e.preventDefault(); // Mencegah form submit
        passInput.classList.remove('border-transparent');
        passInput.classList.add('border-red-500', 'bg-red-50');
        passError.classList.remove('hidden');
        isValid = false;
    }

    // Jika ada yang tidak valid, fokuskan kursor ke field pertama yang salah
    if (!isValid) {
        if (!emailInput.value.trim()) {
            emailInput.focus();
        } else {
            passInput.focus();
        }
    }
});

// Fitur Tambahan: Hapus error saat user mulai mengetik
['email', 'password'].forEach(id => {
    document.getElementById(id).addEventListener('input', function() {
        this.classList.remove('border-red-500', 'bg-red-50');
        this.classList.add('border-transparent');
        document.getElementById(id + '_error').classList.add('hidden');
    });
});
</script>
</body>
</html>