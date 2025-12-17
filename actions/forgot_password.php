<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lupa Password - GKI Scheduler</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Inter', sans-serif; }
        .bg-church {
            background-image: url('../assets/image/backgroundGereja.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
    </style>
</head>
<body class="bg-church min-h-screen flex items-center justify-center relative">
    
    <div class="absolute inset-0 bg-black/60 backdrop-blur-[2px]"></div>

    <div class="relative z-10 w-full max-w-md px-6 py-12">
        <div class="bg-[#18181B]/85 border border-white/10 p-8 rounded-3xl shadow-2xl text-center backdrop-blur-xl">
            
            <div class="mb-8 flex justify-center">
                <img src="../assets/image/GKI_logo.png" alt="Logo GKI" class="h-20 w-auto object-contain">
            </div>

            <h1 class="text-2xl font-bold text-white mb-3">Lupa Password?</h1>
            <p class="text-slate-400 text-sm mb-8 leading-relaxed">
                Masukan alamat email yang terdaftar, dan kami akan mengirimkan link untuk me-reset password Anda.
            </p>

            <?php if(isset($_GET['error'])): ?>
                <div class="bg-rose-500/20 text-rose-300 text-sm p-3 rounded-xl mb-6 border border-rose-500/30 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Email tidak ditemukan.
                </div>
            <?php endif; ?>

            <form id="resetForm" action="send_reset.php" method="POST" class="text-left space-y-6" novalidate>
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Email Address</label>
                    
                    <input type="email" id="email" name="email" placeholder="nama@email.com" 
                        class="w-full bg-[#27272A]/50 border border-white/10 text-white rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-amber-500 outline-none transition placeholder-slate-600">
                    
                    <p id="email_error" class="hidden text-rose-400 text-xs mt-2 font-medium flex items-center animate-pulse">
                        <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Email wajib diisi untuk reset password.
                    </p>
                </div>

                <button type="submit" class="w-full bg-amber-500 hover:bg-amber-400 text-black font-bold py-3.5 rounded-xl shadow-lg transition transform active:scale-95">
                    KIRIM LINK RESET
                </button>
            </form>
            <div class="mt-8">
                <a href="../index.php" class="text-slate-400 hover:text-white text-sm font-medium flex items-center justify-center gap-2 transition">
                    &larr; Kembali ke Login
                </a>
            </div>
            
             <div class="mt-10 border-t border-white/5 pt-6">
                <p class="text-[10px] uppercase tracking-[0.2em] text-slate-600">&copy; 2025 Church Scheduler.</p>
            </div>
        </div>
    </div>

    <div id="modalSuccess" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md transition-opacity duration-300">
        <div class="bg-[#18181B] border border-white/10 p-8 rounded-3xl max-w-sm w-full text-center shadow-2xl transform scale-100">
            <div class="w-20 h-20 bg-green-500/10 text-green-500 rounded-full flex items-center justify-center mx-auto mb-6 border border-green-500/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <h2 class="text-xl font-bold text-white mb-2">Permintaan Terkirim!</h2>
            <p class="text-slate-400 text-sm mb-8 leading-relaxed">
                Silakan periksa kotak masuk email Anda untuk melakukan reset password.
            </p>
            <button onclick="closeModal()" class="w-full bg-white hover:bg-slate-200 text-black font-bold py-3.5 rounded-xl transition shadow-lg">
                Tutup
            </button>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // 1. Logic Modal Success (Existing)
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            
            if (status === 'success') {
                const modal = document.getElementById('modalSuccess');
                modal.classList.remove('hidden');
            }

            // 2. Logic Validasi Custom (New)
            const form = document.getElementById('resetForm');
            const emailInput = document.getElementById('email');
            const emailError = document.getElementById('email_error');

            form.addEventListener('submit', function(e) {
                // Reset Styles
                emailInput.classList.remove('border-rose-500', 'bg-rose-500/10');
                emailInput.classList.add('border-white/10');
                emailError.classList.add('hidden');

                // Cek Validasi
                if (!emailInput.value.trim()) {
                    e.preventDefault(); // Stop submit
                    
                    // Apply Error Styles (Tema Gelap)
                    emailInput.classList.remove('border-white/10');
                    emailInput.classList.add('border-rose-500', 'bg-rose-500/10'); // Merah gelap transparan
                    emailError.classList.remove('hidden');
                    
                    emailInput.focus();
                }
            });

            // Hapus error saat user mengetik
            emailInput.addEventListener('input', function() {
                if(this.classList.contains('border-rose-500')) {
                    this.classList.remove('border-rose-500', 'bg-rose-500/10');
                    this.classList.add('border-white/10');
                    emailError.classList.add('hidden');
                }
            });
        });

        function closeModal() {
            const modal = document.getElementById('modalSuccess');
            modal.classList.add('hidden'); 
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>

</body>
</html>