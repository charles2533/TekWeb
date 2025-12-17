<?php
session_start();
require 'includes/db.php';

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$is_admin = $_SESSION['is_admin'];
$user_name = $_SESSION['user_name'];
$today = date('Y-m-d');

// Ambil Event mendatang
$stmt = $conn->prepare("SELECT * FROM events WHERE date >= :today ORDER BY date ASC, time_start ASC");
$stmt->execute(['today' => $today]);
$upcomingEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Siapkan data untuk FullCalendar (JSON)
$calendarEvents = [];
foreach ($upcomingEvents as $evt) {
    $calendarEvents[] = [
        'id' => $evt['id'],
        'title' => $evt['name'] . ' (' . substr($evt['time_start'], 0, 5) . ')',
        'start' => $evt['date'],
        'allDay' => true,
        'backgroundColor' => $is_admin ? '#1e293b' : '#f59e0b', // Beda warna admin/user
        'borderColor' => $is_admin ? '#1e293b' : '#f59e0b',
        'textColor' => '#ffffff',
        'extendedProps' => [
            'modalId' => $is_admin ? 'edit-event-' . $evt['id'] : 'join-event-' . $evt['id']
        ]
    ];
}

// Ambil Jadwal Saya (Khusus Non-Admin)
$mySchedule = [];
if (!$is_admin) {
    $stmtMy = $conn->prepare("
        SELECT e.*, a.role as my_role, a.id as att_id 
        FROM events e 
        JOIN attendances a ON e.id = a.event_id 
        WHERE a.user_id = ? AND e.date >= ? 
        ORDER BY e.date ASC
    ");
    $stmtMy->execute([$user_id, $today]);
    $mySchedule = $stmtMy->fetchAll(PDO::FETCH_ASSOC);
}

// Helper Function: Hitung Statistik Peserta
function getEventStats($conn, $eventId, $userId) {
    $s1 = $conn->prepare("SELECT COUNT(*) FROM attendances WHERE event_id = ? AND role = 'Operator'");
    $s1->execute([$eventId]);
    $op = $s1->fetchColumn();

    $s2 = $conn->prepare("SELECT COUNT(*) FROM attendances WHERE event_id = ? AND role = 'Cameraman'");
    $s2->execute([$eventId]);
    $cam = $s2->fetchColumn();

    $s3 = $conn->prepare("SELECT a.id as att_id, a.role, u.name, u.id as user_id FROM attendances a JOIN users u ON a.user_id = u.id WHERE a.event_id = ?");
    $s3->execute([$eventId]);
    $attendees = $s3->fetchAll(PDO::FETCH_ASSOC);

    $myRole = null;
    foreach($attendees as $att) {
        if($att['user_id'] == $userId) $myRole = $att['role'];
    }

    return ['op' => $op, 'cam' => $cam, 'attendees' => $attendees, 'my_role' => $myRole];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - GKI Scheduler</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//unpkg.com/alpinejs" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 2px; }
        
        /* FullCalendar Customization */
        .fc-event { cursor: pointer; border: none; }
        .fc-toolbar-title { font-size: 1.25rem !important; font-weight: 700; }
        .fc-button { background-color: #1e293b !important; border: none !important; font-size: 0.8rem !important; font-weight: 600 !important; }
        .fc-daygrid-day-number { font-weight: 600; color: #64748b; }
    </style>
</head>

<body class="antialiased" x-data="{ activeModal: null }" @keydown.escape.window="activeModal = null">

    <nav class="<?= $is_admin ? 'bg-white border-b border-slate-200' : 'bg-[#18181B] border-b border-white/10' ?> p-4 relative z-50">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            
            <div class="flex items-center">
                <img src="assets/image/GKI_logo.png" 
                     alt="GKI Logo" 
                     class="h-12 w-auto object-contain"
                     href="dashboard.php">
                     
            </div>

            <div class="flex items-center gap-4 <?= $is_admin ? 'text-slate-600' : 'text-slate-300' ?>">
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="flex items-center gap-2 font-bold focus:outline-none hover:opacity-80 transition py-2">
                        <span>Halo, <b><?= htmlspecialchars($user_name) ?></b></span>
                        <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <div x-show="open" 
                         @click.away="open = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-slate-100 py-1 ring-1 ring-black ring-opacity-5 focus:outline-none z-[100]"
                         style="display: none;">
                        
                        <div class="px-4 py-2 border-b border-slate-100">
                            <p class="text-xs text-slate-500">Signed in as</p>
                            <p class="text-sm font-bold text-slate-800 truncate"><?= htmlspecialchars($user_name) ?></p>
                        </div>
                        <a href="profile.php" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-blue-600 flex items-center gap-2 transition">
                            Edit Profile
                        </a>
                        <a href="actions/logout.php" class="block px-4 py-2 text-sm text-rose-600 hover:bg-rose-50 font-bold flex items-center gap-2 transition rounded-b-xl">
                            Log Out
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <?php if ($is_admin): ?>
    <div class="min-h-screen bg-slate-50 text-slate-800 pb-12">
        <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-10 gap-6">
                <div>
                    <h1 class="text-5xl font-extrabold text-slate-900 tracking-tight">Hello <br> <span class="text-blue-800">Admin</span> 👋</h1>
                    <p class="text-slate-500 mt-2 font-medium">Kelola jadwal pelayanan gereja dengan mudah.</p>
                </div>
                <div class="w-full md:w-1/3 bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex items-center justify-between relative overflow-hidden group hover:shadow-md transition-all">
                    <div class="relative z-10">
                        <span class="text-slate-400 text-xs font-bold uppercase tracking-wider">Total Events</span>
                        <div class="text-5xl font-bold text-slate-800 mt-2"><?= count($upcomingEvents) ?></div>
                    </div>
                    <div class="absolute right-0 top-0 h-full w-24 bg-gradient-to-l from-blue-50 to-transparent"></div>
                    <div class="relative z-10 bg-blue-100 text-blue-700 p-3 rounded-xl border border-blue-200">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
                <button type="button" @click="activeModal = 'create-event'" class="h-28 bg-white border-2 border-slate-100 hover:border-blue-600 rounded-2xl flex items-center justify-center gap-4 hover:bg-blue-50/30 transition-all shadow-sm hover:shadow-md group">
                    <div class="bg-blue-50 p-3 rounded-full text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    </div>
                    <span class="text-xl font-bold text-slate-700 group-hover:text-blue-900">Create Event</span>
                </button>
                <button onclick="document.getElementById('fullCalendarSection').scrollIntoView({behavior: 'smooth'})" class="h-28 bg-white border-2 border-slate-100 hover:border-amber-500 rounded-2xl flex items-center justify-center gap-4 hover:bg-amber-50/30 transition-all shadow-sm hover:shadow-md group">
                    <div class="bg-amber-50 p-3 rounded-full text-amber-500 group-hover:bg-amber-500 group-hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <span class="text-xl font-bold text-slate-700 group-hover:text-amber-700">Lihat Kalender</span>
                </button>
            </div>

            <div id="fullCalendarSection" class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                <div class="flex justify-between items-center mb-6 border-b border-slate-100 pb-4">
                    <h3 class="text-xl font-bold text-slate-800">Kalender Pelayanan</h3>
                </div>
                <div id='calendar' class="text-slate-600 font-sans"></div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <div class="min-h-screen bg-[#0F1014] text-slate-300 pb-12">
        <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="flex flex-col md:flex-row justify-between items-end mb-10 gap-6">
                <div>
                    <h1 class="text-4xl font-extrabold text-white tracking-tight">Welcome back, <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-200 to-amber-500"><?= htmlspecialchars($user_name) ?></span> 👋</h1>
                    <p class="text-slate-400 mt-2 font-medium">Siap melayani Tuhan hari ini?</p>
                </div>
                <div class="bg-[#18181B] p-5 rounded-2xl shadow-2xl shadow-black/50 border border-white/5 flex items-center gap-5 min-w-[200px] relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-amber-500/10 blur-2xl rounded-full -mr-10 -mt-10"></div>
                    <div class="relative z-10 text-right w-full">
                        <span class="block text-3xl font-bold text-white"><?= count($mySchedule) ?></span>
                        <span class="text-xs text-slate-500 font-bold uppercase tracking-wider group-hover:text-amber-500/80 transition">Jadwal Saya</span>
                    </div>
                </div>
            </div>

            <div class="mb-12">
                <h3 class="text-lg font-bold text-white mb-5 flex items-center gap-2">
                    <div class="w-1 h-6 bg-gradient-to-b from-amber-300 to-amber-600 rounded-full shadow-[0_0_10px_rgba(245,158,11,0.5)]"></div>
                    Jadwal Pelayanan Anda
                </h3>
                <?php if(empty($mySchedule)): ?>
                    <div class="bg-[#18181B] border-2 border-dashed border-slate-700 rounded-2xl p-10 text-center">
                        <p class="text-slate-400 font-medium">Belum ada jadwal yang diambil.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach($mySchedule as $my): ?>
                        <div class="bg-[#18181B] rounded-2xl shadow-lg border border-white/5 p-6 hover:-translate-y-1 transition group relative overflow-hidden">
                            <div class="flex justify-between items-start mb-4 relative z-10">
                                <span class="inline-flex px-3 py-1 rounded-lg text-xs font-bold uppercase border <?= $my['my_role'] == 'Operator' ? 'bg-cyan-950/50 text-cyan-400 border-cyan-800/50' : 'bg-amber-950/50 text-amber-400 border-amber-800/50' ?>">
                                    <?= $my['my_role'] ?>
                                </span>
                                <span class="text-sm font-bold text-slate-500"><?= date('d M', strtotime($my['date'])) ?></span>
                            </div>
                            <h4 class="font-bold text-slate-200 text-xl mb-2 group-hover:text-amber-400 transition relative z-10"><?= htmlspecialchars($my['name']) ?></h4>
                            <div class="flex items-center gap-2 text-slate-400 text-sm mb-6 relative z-10">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <?= substr($my['time_start'], 0, 5) ?> - <?= substr($my['time_end'], 0, 5) ?>
                            </div>
                            <div class="pt-4 border-t border-white/5 flex justify-end relative z-10">
                                <button type="button" @click="activeModal = 'cancel-event-<?= $my['id'] ?>'" class="text-xs font-bold text-slate-500 hover:text-rose-500 px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> Batalkan
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <h3 class="text-lg font-bold text-white mb-5 flex items-center gap-2"><div class="w-1 h-6 bg-slate-600 rounded-full"></div> Daftar Event Tersedia</h3>
                <div class="bg-[#18181B] rounded-2xl shadow-xl border border-white/5 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-black/20 border-b border-white/5">
                            <tr>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Event Details</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider hidden md:table-cell">Date & Time</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach($upcomingEvents as $evt): 
                                $stats = getEventStats($conn, $evt['id'], $user_id);
                                $isFull = ($stats['op'] >= $evt['req_operator'] && $stats['cam'] >= $evt['req_cameraman']);
                            ?>
                            <tr class="hover:bg-white/[0.02] transition">
                                <td class="px-6 py-5">
                                    <div class="font-bold text-slate-200 text-base"><?= htmlspecialchars($evt['name']) ?></div>
                                    <div class="text-xs text-slate-500 flex items-center gap-1 mt-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg><?= htmlspecialchars($evt['place']) ?></div>
                                </td>
                                <td class="px-6 py-5 hidden md:table-cell">
                                    <div class="text-sm font-bold text-slate-300"><?= date('d M Y', strtotime($evt['date'])) ?></div>
                                    <div class="text-xs text-slate-600 font-medium"><?= substr($evt['time_start'], 0, 5) ?> - <?= substr($evt['time_end'], 0, 5) ?></div>
                                </td>
                                <td class="px-6 py-5 text-center">
                                    <?php if($stats['my_role']): ?><span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-900/30 text-emerald-400 border border-emerald-500/30">Terdaftar</span>
                                    <?php elseif($isFull): ?><span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-800 text-slate-500 border border-slate-700">Penuh</span>
                                    <?php else: ?><span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-900/30 text-blue-400 border border-blue-500/30">Tersedia</span><?php endif; ?>
                                </td>
                                <td class="px-6 py-5 text-right">
                                    <?php if(!$stats['my_role'] && !$isFull): ?>
                                        <button type="button" @click="activeModal = 'join-event-<?= $evt['id'] ?>'" class="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-black text-xs font-bold px-5 py-2.5 rounded-lg shadow-lg shadow-amber-500/20 active:scale-95 transition">Ambil Slot</button>
                                    <?php elseif($stats['my_role']): ?>
                                        <span class="text-xs font-bold text-slate-600 uppercase tracking-wide">Joined as <?= $stats['my_role'] ?></span>
                                    <?php else: ?><span class="text-slate-700">—</span><?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            
        </div>
    </div>
    <?php endif; ?>

    <?php if ($is_admin): ?>
    <div 
        x-show="activeModal === 'create-event'" 
        x-cloak 
        class="fixed inset-0 z-[99] flex items-center justify-center bg-black/50 backdrop-blur-sm" 
        x-transition.opacity
    >
        <div 
            class="bg-white p-8 rounded-xl w-full max-w-md shadow-2xl relative" 
            @click.away="activeModal = null"
            x-data="{
                formData: {
                    name: '',
                    place: '',
                    date: '',
                    time_start: '',
                    time_end: ''
                },
                errors: {},
                validateAndSubmit() {
                    this.errors = {}; // Reset error
                    
                    // Logic Validasi Manual
                    if (!this.formData.name) this.errors.name = 'Nama event wajib diisi!';
                    if (!this.formData.place) this.errors.place = 'Tempat wajib diisi!';
                    if (!this.formData.date) this.errors.date = 'Tanggal wajib diisi!';
                    if (!this.formData.time_start) this.errors.time_start = 'Waktu mulai wajib!';
                    if (!this.formData.time_end) this.errors.time_end = 'Waktu selesai wajib!';

                    // Jika tidak ada error, submit form secara manual via referensi DOM
                    if (Object.keys(this.errors).length === 0) {
                        $refs.eventForm.submit();
                    }
                }
            }"
        >
            <h2 class="text-xl font-bold text-slate-800 mb-6 border-b-2 border-slate-100 pb-2">Create New Event</h2>
            
            <form action="actions/store_event.php" method="POST" class="space-y-4" novalidate x-ref="eventForm" @submit.prevent="validateAndSubmit">
                
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-400">Nama Event</label>
                    <input 
                        type="text" 
                        name="name" 
                        x-model="formData.name"
                        :class="errors.name ? 'border-red-500 focus:border-red-500 text-red-600' : 'border-slate-200 focus:border-blue-900 text-slate-700'"
                        class="w-full border-x-0 border-t-0 border-b-2 px-0 py-1 font-bold placeholder-slate-300 focus:ring-0 transition-colors" 
                        placeholder="Isi nama event..."
                    >
                    <p x-show="errors.name" x-text="errors.name" class="text-red-500 text-xs mt-1 italic"></p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-400">Tempat</label>
                    <input 
                        type="text" 
                        name="place" 
                        x-model="formData.place"
                        :class="errors.place ? 'border-red-500 focus:border-red-500 text-red-600' : 'border-slate-200 focus:border-blue-900 text-slate-700'"
                        class="w-full border-x-0 border-t-0 border-b-2 px-0 py-1 font-bold focus:ring-0 transition-colors"
                    >
                    <p x-show="errors.place" x-text="errors.place" class="text-red-500 text-xs mt-1 italic"></p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-400">Tanggal</label>
                        <input 
                            type="date" 
                            name="date" 
                            x-model="formData.date"
                            :class="errors.date ? 'border-red-500 focus:border-red-500 text-red-600' : 'border-slate-200 focus:border-blue-900 text-slate-700'"
                            class="w-full border-x-0 border-t-0 border-b-2 px-0 py-1 font-bold focus:ring-0 transition-colors"
                        >
                        <p x-show="errors.date" x-text="errors.date" class="text-red-500 text-xs mt-1 italic"></p>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-400">Start</label>
                            <input 
                                type="time" 
                                name="time_start" 
                                x-model="formData.time_start"
                                :class="errors.time_start ? 'border-red-500 focus:border-red-500 text-red-600' : 'border-slate-200 focus:border-blue-900 text-slate-700'"
                                class="w-full border-x-0 border-t-0 border-b-2 px-0 py-1 focus:ring-0 transition-colors"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-400">End</label>
                            <input 
                                type="time" 
                                name="time_end" 
                                x-model="formData.time_end"
                                :class="errors.time_end ? 'border-red-500 focus:border-red-500 text-red-600' : 'border-slate-200 focus:border-blue-900 text-slate-700'"
                                class="w-full border-x-0 border-t-0 border-b-2 px-0 py-1 focus:ring-0 transition-colors"
                            >
                        </div>
                    </div>
                </div>
                
                <div x-show="errors.time_start || errors.time_end" class="text-red-500 text-xs italic">
                    * Waktu mulai dan selesai harus diisi
                </div>

                <div class="grid grid-cols-2 gap-4 pt-2">
                    <div><label class="block text-xs font-bold uppercase text-slate-400">Jumlah Operator</label><input type="number" name="req_operator" value="1" class="w-full border-2 border-slate-200 rounded px-2 py-1 font-bold text-slate-700"></div>
                    <div><label class="block text-xs font-bold uppercase text-slate-400">Jumlah Cameraman</label><input type="number" name="req_cameraman" value="1" class="w-full border-2 border-slate-200 rounded px-2 py-1 font-bold text-slate-700"></div>
                </div>
                
                <div class="mt-8 flex justify-end gap-3">
                    <button type="button" @click="activeModal = null" class="text-slate-400 font-bold text-sm hover:text-slate-600">Cancel</button>
                    <button type="submit" class="bg-blue-900 text-white px-6 py-2 rounded font-bold hover:bg-blue-800 shadow-md">Create</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php foreach($upcomingEvents as $evt): $stats = getEventStats($conn, $evt['id'], $user_id); ?>
    <div x-show="activeModal === 'edit-event-<?= $evt['id'] ?>'" x-cloak class="fixed inset-0 z-[99] flex items-center justify-center bg-black/60 backdrop-blur-sm" x-transition.opacity>
        <div 
            class="bg-white p-8 rounded-xl w-full max-w-lg shadow-2xl relative max-h-[90vh] overflow-y-auto custom-scrollbar" 
            @click.away="activeModal = null"
            x-data="{ 
                currentView: 'edit',
                formData: {
                    name: `<?= htmlspecialchars($evt['name'], ENT_QUOTES) ?>`, 
                    place: `<?= htmlspecialchars($evt['place'], ENT_QUOTES) ?>`,
                    date: '<?= $evt['date'] ?>',
                    time_start: '<?= $evt['time_start'] ?>',
                    time_end: '<?= $evt['time_end'] ?>'
                },
                errors: {},
                validateUpdate() {
                    this.errors = {};
                    
                    if (!this.formData.name) this.errors.name = 'Nama event wajib diisi';
                    if (!this.formData.place) this.errors.place = 'Tempat wajib diisi';
                    if (!this.formData.date) this.errors.date = 'Tanggal wajib diisi';
                    if (!this.formData.time_start) this.errors.time_start = 'Waktu mulai wajib';
                    if (!this.formData.time_end) this.errors.time_end = 'Waktu selesai wajib';

                    if (Object.keys(this.errors).length === 0) {
                        $refs.updateForm.submit();
                    }
                }
            }" 
        >
            
            <div x-show="currentView === 'edit'" x-transition:enter.duration.300ms>
                <div class="flex justify-center mb-6 border-b border-slate-100 pb-4">
                    <form id="form-delete-event" action="actions/delete_event.php" method="POST" onsubmit="return confirm('Yakin hapus event ini?');" class="w-full">
                        <input type="hidden" name="event_id" value="<?= $evt['id'] ?>">
                        <button type="button" onclick="konfirmasiHapusEvent()" class="w-full border border-rose-200 text-rose-600 px-4 py-2 rounded-lg text-sm font-bold hover:bg-rose-50 transition flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg> Hapus Event Ini
                        </button>
                    </form>
                </div>

                <form action="actions/update_event.php" method="POST" class="space-y-5" novalidate x-ref="updateForm" @submit.prevent="validateUpdate">
                    <input type="hidden" name="event_id" value="<?= $evt['id'] ?>">
                    
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">NAME</label>
                        <input 
                            type="text" 
                            name="name" 
                            x-model="formData.name"
                            :class="errors.name ? 'border-red-500 text-red-600 focus:border-red-500' : 'border-slate-200 text-slate-800 focus:border-blue-800'"
                            class="w-full border-x-0 border-t-0 border-b-2 focus:ring-0 px-0 py-2 font-bold text-lg transition-colors" 
                        >
                        <p x-show="errors.name" x-text="errors.name" class="text-red-500 text-[10px] font-bold mt-1"></p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">PLACE</label>
                        <input 
                            type="text" 
                            name="place" 
                            x-model="formData.place"
                            :class="errors.place ? 'border-red-500 text-red-600 focus:border-red-500' : 'border-slate-200 text-slate-800 focus:border-blue-800'"
                            class="w-full border-x-0 border-t-0 border-b-2 focus:ring-0 px-0 py-2 font-medium transition-colors" 
                        >
                        <p x-show="errors.place" x-text="errors.place" class="text-red-500 text-[10px] font-bold mt-1"></p>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-1/2">
                            <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">DATE</label>
                            <input 
                                type="date" 
                                name="date" 
                                x-model="formData.date"
                                :class="errors.date ? 'border-red-500 text-red-600 focus:border-red-500' : 'border-slate-200 text-slate-800 focus:border-blue-800'"
                                class="w-full border-x-0 border-t-0 border-b-2 focus:ring-0 px-0 py-2 font-medium transition-colors" 
                            >
                            <p x-show="errors.date" x-text="errors.date" class="text-red-500 text-[10px] font-bold mt-1"></p>
                        </div>

                        <div class="w-1/4">
                            <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">START</label>
                            <input 
                                type="time" 
                                name="time_start" 
                                x-model="formData.time_start"
                                :class="errors.time_start ? 'border-red-500 text-red-600 focus:border-red-500' : 'border-slate-200 text-slate-800 focus:border-blue-800'"
                                class="w-full border-x-0 border-t-0 border-b-2 focus:ring-0 px-0 py-2 font-medium transition-colors" 
                            >
                        </div>

                        <div class="w-1/4">
                            <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">END</label>
                            <input 
                                type="time" 
                                name="time_end" 
                                x-model="formData.time_end"
                                :class="errors.time_end ? 'border-red-500 text-red-600 focus:border-red-500' : 'border-slate-200 text-slate-800 focus:border-blue-800'"
                                class="w-full border-x-0 border-t-0 border-b-2 focus:ring-0 px-0 py-2 font-medium transition-colors" 
                            >
                        </div>
                    </div>
                    
                    <div x-show="errors.time_start || errors.time_end" class="text-red-500 text-[10px] font-bold">
                        * Waktu mulai dan selesai wajib diisi
                    </div>

                    <div class="flex justify-between items-center pt-6 mt-2 border-t border-slate-100">
                        <button type="button" @click="currentView = 'list'" class="group flex items-center gap-2 text-blue-800 font-bold text-sm hover:text-blue-900 transition"><span class="border border-blue-800 rounded px-1.5 py-0.5 text-[10px] group-hover:bg-blue-800 group-hover:text-white transition">LIST</span> <span>Peserta (<?= count($stats['attendees']) ?>)</span></button>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="activeModal = null" class="text-slate-400 font-bold text-sm hover:text-slate-600 transition">Batal</button>
                            <button type="submit" class="bg-blue-900 text-white px-6 py-2 rounded-lg font-bold text-sm hover:bg-blue-800 shadow-lg shadow-blue-900/20 transition">Simpan</button>
                        </div>
                    </div>
                </form>
            </div>

            <div x-show="currentView === 'list'" style="display: none;">
                <div class="flex justify-between items-center mb-6 border-b border-slate-100 pb-4">
                    <h2 class="text-lg font-bold text-slate-800">Daftar Peserta</h2>
                    <button @click="currentView = 'edit'" class="text-xs font-bold text-slate-400 hover:text-blue-800 flex items-center gap-1 transition">&larr; Kembali</button>
                </div>
                <div class="space-y-3 mb-6 max-h-60 overflow-y-auto pr-1 custom-scrollbar">
                    <?php if(empty($stats['attendees'])): ?>
                        <div class="text-center py-8 bg-slate-50 rounded-lg border border-slate-200 border-dashed"><p class="text-slate-400 text-sm italic">Belum ada peserta terdaftar.</p></div>
                    <?php else: ?>
                        <?php foreach($stats['attendees'] as $att): ?>
                        <div class="flex justify-between items-center bg-white border border-slate-200 p-4 rounded-xl hover:shadow-md transition">
                            <div>
                                <div class="font-bold text-slate-800 text-base"><?= htmlspecialchars($att['name']) ?></div>
                                <div class="text-[10px] font-bold mt-1 uppercase tracking-wider <?= $att['role'] == 'Operator' ? 'text-cyan-600' : 'text-amber-600' ?>"><?= $att['role'] ?></div>
                            </div>
                            <form id="form-remove-<?= $att['user_id'] ?>" action="actions/admin_remove_attendee.php" method="POST">
                                <input type="hidden" name="event_id" value="<?= $evt['id'] ?>">
                                <input type="hidden" name="user_id" value="<?= $att['user_id'] ?>">
                                <button type="button" onclick="konfirmasiKeluarkanPeserta(<?= $att['user_id'] ?>, '<?= htmlspecialchars($att['name'], ENT_QUOTES) ?>')" class="w-8 h-8 flex items-center justify-center rounded-full text-slate-300 hover:text-rose-500 hover:bg-rose-50 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="flex justify-end pt-4 border-t border-slate-100"><button type="button" @click="activeModal = null" class="bg-slate-100 text-slate-600 px-5 py-2 rounded-lg font-bold hover:bg-slate-200 transition text-sm">Tutup</button></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!$is_admin): ?>
        <?php foreach($upcomingEvents as $evt): 
            $stats = getEventStats($conn, $evt['id'], $user_id);
            $opFull = $stats['op'] >= $evt['req_operator'];
            $camFull = $stats['cam'] >= $evt['req_cameraman'];
        ?>
        
        <div x-show="activeModal === 'join-event-<?= $evt['id'] ?>'" x-cloak class="fixed inset-0 z-[99] flex items-center justify-center bg-black/80 backdrop-blur-sm" x-transition.opacity>
            <form action="actions/join_event.php" method="POST" class="bg-[#101012] w-full max-w-[500px] rounded-2xl border border-white/10 relative overflow-hidden shadow-2xl" @click.away="activeModal = null">
                <input type="hidden" name="event_id" value="<?= $evt['id'] ?>">
                
                <div class="px-8 py-6 border-b border-white/5 bg-gradient-to-r from-amber-500/10 to-transparent">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <span class="text-amber-500">✦</span> Konfirmasi Jadwal
                    </h2>
                    <p class="text-slate-400 text-xs mt-1">Pastikan Anda bersedia hadir tepat waktu di <span class="text-slate-300 font-bold"><?= htmlspecialchars($evt['place']) ?></span>.</p>
                </div>

                <div class="p-8 space-y-6">
                    <div>
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3 block">Pilih Role Pelayanan</label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="relative cursor-pointer group">
                                <input type="radio" name="role" value="Operator" class="peer sr-only" <?= $opFull ? 'disabled' : '' ?> required>
                                <div class="p-4 rounded-xl border-2 transition-all flex flex-col items-center gap-2
                                    <?= $opFull 
                                        ? 'border-slate-800 bg-slate-900/50 opacity-50 cursor-not-allowed' 
                                        : 'border-slate-700 bg-slate-800/50 hover:border-cyan-500 peer-checked:border-cyan-500 peer-checked:bg-cyan-900/20 peer-checked:text-cyan-400 text-slate-400' 
                                    ?>">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    <span class="font-bold text-sm">Operator</span>
                                    <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded bg-black/30">
                                        <?= $stats['op'] ?> / <?= $evt['req_operator'] ?>
                                    </span>
                                </div>
                            </label>

                            <label class="relative cursor-pointer group">
                                <input type="radio" name="role" value="Cameraman" class="peer sr-only" <?= $camFull ? 'disabled' : '' ?> required>
                                <div class="p-4 rounded-xl border-2 transition-all flex flex-col items-center gap-2
                                    <?= $camFull 
                                        ? 'border-slate-800 bg-slate-900/50 opacity-50 cursor-not-allowed' 
                                        : 'border-slate-700 bg-slate-800/50 hover:border-amber-500 peer-checked:border-amber-500 peer-checked:bg-amber-900/20 peer-checked:text-amber-400 text-slate-400' 
                                    ?>">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                    <span class="font-bold text-sm">Cameraman</span>
                                    <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded bg-black/30">
                                        <?= $stats['cam'] ?> / <?= $evt['req_cameraman'] ?>
                                    </span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="px-8 py-6 bg-black/20 border-t border-white/5 flex justify-between items-center">
                    <button type="button" @click="activeModal = null" class="text-slate-500 hover:text-slate-300 font-bold text-sm transition">Batalkan</button>
                    <button type="submit" class="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-black px-8 py-2.5 rounded-lg font-bold shadow-lg shadow-amber-900/20 transition transform active:scale-95">
                        Saya Bersedia
                    </button>
                </div>
            </form>
        </div>
        <?php endforeach; ?>

        <?php foreach($mySchedule as $my): ?>
        <div x-show="activeModal === 'cancel-event-<?= $my['id'] ?>'" x-cloak class="fixed inset-0 z-[99] flex items-center justify-center bg-black/80 backdrop-blur-sm" x-transition.opacity>
             <div class="bg-[#101012] w-full max-w-sm rounded-2xl border border-white/10 p-6 shadow-2xl text-center" @click.away="activeModal = null">
                 <div class="w-12 h-12 rounded-full bg-rose-500/10 text-rose-500 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                 </div>
                 <h3 class="text-white font-bold text-lg mb-2">Batalkan Pelayanan?</h3>
                 <p class="text-slate-400 text-sm mb-6">Anda akan dihapus dari daftar <span class="text-amber-500 font-bold"><?= $my['my_role'] ?></span> pada event ini.</p>
                 
                 <form action="actions/cancel_event.php" method="POST" class="flex gap-3">
                     <input type="hidden" name="event_id" value="<?= $my['id'] ?>">
                     <button type="button" @click="activeModal = null" class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 py-2.5 rounded-lg font-bold text-sm transition">Kembali</button>
                     <button type="submit" class="flex-1 bg-rose-600 hover:bg-rose-500 text-white py-2.5 rounded-lg font-bold text-sm transition">Ya, Batalkan</button>
                 </form>
             </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <script>
        function konfirmasiHapusEvent() {
            Swal.fire({
                title: 'Hapus event?',
                text: "Seluruh data peserta di event ini juga akan terhapus.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                background: '#1f2937', // Dark mode background
                color: '#fff'          // Text color
            }).then((result) => {
                if (result.isConfirmed) {
                    // Jika user klik Ya, submit form secara manual via JS
                    document.getElementById('form-delete-event').submit();
                }
            })
        }
     
        function konfirmasiKeluarkanPeserta(idUser, namaPeserta) {
            // Cek di Console browser apakah fungsi terpanggil
            console.log("Tombol ditekan untuk ID:", idUser); 

            Swal.fire({
                title: 'Keluarkan Peserta?',
                text: "Yakin ingin mengeluarkan " + namaPeserta + " dari event ini?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Keluarkan!',
                cancelButtonText: 'Batal',
                background: '#1f2937', 
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Debugging: Pastikan form ditemukan
                    var formTarget = document.getElementById('form-remove-' + idUser);
                    if (formTarget) {
                        formTarget.submit();
                    } else {
                        Swal.fire('Error', 'Form tidak ditemukan untuk ID: ' + idUser, 'error');
                    }
                }
            })
        }

        document.addEventListener('DOMContentLoaded', function() {
            // 1. FullCalendar Integration
            var calendarEl = document.getElementById('calendar');
            if (calendarEl) {
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,listWeek'
                    },
                    height: 'auto',
                    events: <?= json_encode($calendarEvents) ?>,
                    eventClick: function(info) {
                        // Trigger Alpine Modal based on ID
                        let modalId = info.event.extendedProps.modalId;
                        if(modalId) {
                            window.dispatchEvent(new CustomEvent('open-modal', { detail: modalId }));
                        }
                    }
                });
                calendar.render();
            }

            // Listen for Alpine event to update modal state
            window.addEventListener('open-modal', event => {
                let alpineData = Alpine.$data(document.querySelector('[x-data]'));
                alpineData.activeModal = event.detail;
            });
        });

        // 2. SweetAlert2 Logic (Check PHP Session Flash)
        <?php if(isset($_SESSION['flash_success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?= $_SESSION['flash_success'] ?>',
                background: '<?= $is_admin ? "#fff" : "#18181b" ?>',
                color: '<?= $is_admin ? "#000" : "#fff" ?>',
                confirmButtonColor: '#3b82f6'
            });
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if(isset($_SESSION['flash_error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: '<?= $_SESSION['flash_error'] ?>',
                background: '<?= $is_admin ? "#fff" : "#18181b" ?>',
                color: '<?= $is_admin ? "#000" : "#fff" ?>',
                confirmButtonColor: '#e11d48'
            });
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>
    </script>
</body>
</html>