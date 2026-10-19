
<?php
require_once __DIR__ . '/functions.php';
wajib_login();

$role = $_SESSION['role'] ?? '';
$nama = $_SESSION['nama_lengkap'] ?? 'Pengguna';

$menu = [
    'admin' => [
        ['Dashboard', '/admin/dashboard.php'],
        ['Manajemen User', '/admin/users.php'],
        ['Tarif Parkir', '/admin/tarif.php'],
        ['Area Parkir', '/admin/area.php'],
        ['Data Kendaraan', '/admin/kendaraan.php'],
        ['Log Aktivitas', '/admin/log.php'],
    ],
    'petugas' => [
        ['Kendaraan Masuk', '/petugas/masuk.php'],
        ['Transaksi Parkir', '/petugas/transaksi.php'],
    ],
    'owner' => [
        ['Dashboard Owner', '/owner/dashboard.php'],
        ['Laporan Transaksi', '/owner/laporan.php'],
    ],
];

$judul = $judul ?? $pageTitle ?? 'Aplikasi Parkir';
$halaman = basename($_SERVER['SCRIPT_NAME'] ?? '');
$kelasHalaman = [
    'dashboard.php' => 'page-dashboard',
    'masuk.php' => 'page-operations',
    'transaksi.php' => 'page-operations',
    'kendaraan.php' => 'page-fleet',
    'area.php' => 'page-management',
    'tarif.php' => 'page-management',
    'users.php' => 'page-management',
    'log.php' => 'page-management',
    'laporan.php' => 'page-reports',
    'struk.php' => 'page-reports',
][$halaman] ?? 'page-general';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judul) ?> | Aplikasi Parkir</title>
    <script src="/aplikasi-parkir/assets/js/theme.js"></script>
    <link rel="stylesheet" href="/aplikasi-parkir/assets/css/style.css?v=20261019-1">
</head>
<body class="<?= e($kelasHalaman) ?>">
<header class="topbar">
    <a class="brand" href="/aplikasi-parkir/">
        <span class="brand-icon" aria-hidden="true">
            <svg viewBox="0 0 32 32">
                <path d="M7 27V5h10a7 7 0 0 1 0 14H7" />
            </svg>
        </span>
        <span>APLIKASI PARKIR</span>
    </a>

    <div class="userbox">
        <span><?= e($nama) ?> (<?= e(ucfirst($role)) ?>)</span>
        <button class="theme-toggle" type="button" aria-pressed="false">Mode malam</button>
        <a href="/aplikasi-parkir/auth/logout.php">Logout</a>
    </div>
</header>

<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-heading">
            MENU <?= e(strtoupper($role)) ?>
        </div>

        <?php foreach ($menu[$role] ?? [] as $item): ?>
            <a href="/aplikasi-parkir<?= e($item[1]) ?>">
                <?= e($item[0]) ?>
            </a>
        <?php endforeach; ?>
    </aside>

    <main class="content">
        <div class="feature-illustration" aria-hidden="true"></div>
