
<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/functions.php';

if (!empty($_SESSION['id_user'])) {
    redirect_role();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id_user, nama_lengkap, username, password,
                    role, status_aktif
             FROM tb_user
             WHERE username = ?
             LIMIT 1'
        );

        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $valid = false;

        if ($user && (int) $user['status_aktif'] === 1) {
            $hash = (string) $user['password'];

            if (password_verify($password, $hash)) {
                $valid = true;
            } elseif (
                preg_match('/^[a-f0-9]{64}$/i', $hash) &&
                hash_equals(
                    strtolower($hash),
                    hash('sha256', $password)
                )
            ) {
                $valid = true;
            }
        }

        if ($valid) {
            session_regenerate_id(true);

            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            catat_aktivitas($pdo, 'Login ke sistem');

            redirect_role();
        } else {
            $error = 'Username atau password salah, atau akun tidak aktif.';
        }
    }
}

$slotTersedia = [
    'motor' => ['tersedia' => 0, 'kapasitas' => 0, 'terisi' => 0, 'area_penuh' => 0, 'jumlah_area' => 0],
    'mobil' => ['tersedia' => 0, 'kapasitas' => 0, 'terisi' => 0, 'area_penuh' => 0, 'jumlah_area' => 0],
    'lainnya' => ['tersedia' => 0, 'kapasitas' => 0, 'terisi' => 0, 'area_penuh' => 0, 'jumlah_area' => 0],
];
$areaSlots = $pdo->query(
    "SELECT area.kategori,
            SUM(area.kapasitas) AS kapasitas,
            SUM(GREATEST(area.kapasitas - area.terisi, 0)) AS tersedia,
            SUM(area.terisi) AS terisi,
            COUNT(*) AS jumlah_area,
            SUM(CASE
                    WHEN area.kapasitas > 0 AND area.terisi >= area.kapasitas THEN 1
                    ELSE 0
                END) AS area_penuh
     FROM (
         SELECT a.id_area, a.kapasitas, COALESCE(parkir.terisi, 0) AS terisi,
                CASE
                    WHEN LOWER(a.nama_area) LIKE '%motor%' THEN 'motor'
                    WHEN LOWER(a.nama_area) LIKE '%mobil%' THEN 'mobil'
                    ELSE 'lainnya'
                END AS kategori
         FROM tb_area_parkir a
         LEFT JOIN (
             SELECT id_area, COUNT(*) AS terisi
             FROM tb_transaksi
             WHERE status = 'masuk'
             GROUP BY id_area
         ) parkir ON parkir.id_area = a.id_area
     ) area
     GROUP BY area.kategori"
)->fetchAll(PDO::FETCH_ASSOC);

foreach ($areaSlots as $areaSlot) {
    $slotTersedia[$areaSlot['kategori']] = [
        'tersedia' => (int) $areaSlot['tersedia'],
        'kapasitas' => (int) $areaSlot['kapasitas'],
        'terisi' => (int) $areaSlot['terisi'],
        'area_penuh' => (int) $areaSlot['area_penuh'],
        'jumlah_area' => (int) $areaSlot['jumlah_area'],
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Aplikasi Parkir</title>
    <script src="../assets/js/theme.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-page">

<main class="login-layout">
    <section class="login-hero">
        <div class="login-brand-mark" aria-hidden="true">
            <svg viewBox="0 0 32 32">
                <path d="M7 27V5h10a7 7 0 0 1 0 14H7" />
            </svg>
        </div>
        <div class="hero-tag">INFORMASI PARKIR</div>
        <h1>Tempat parkir tersedia</h1>
        <p class="parking-availability-note">
            Sisa kapasitas berdasarkan area parkir yang tercatat saat ini.
        </p>

        <div class="parking-availability">
            <div class="parking-availability-item is-motor">
                <div class="parking-availability-heading">
                    <span class="parking-availability-icon" aria-hidden="true">
                        <svg viewBox="0 0 32 32"><circle cx="8" cy="23" r="4"/><circle cx="25" cy="23" r="4"/><path d="m8 23 6-10h6l5 10m-11-10-3-3m3 3 5 10h6M18 9h4"/></svg>
                    </span>
                    <span class="parking-availability-type">Motor</span>
                </div>
                <div class="parking-availability-count">
                    <strong><?= number_format($slotTersedia['motor']['tersedia']) ?></strong>
                    <span>slot<br>tersedia</span>
                </div>
                <div class="parking-availability-meta">
                    <span>Terisi <strong><?= number_format($slotTersedia['motor']['terisi']) ?></strong> / <?= number_format($slotTersedia['motor']['kapasitas']) ?></span>
                    <span class="<?= $slotTersedia['motor']['area_penuh'] > 0 ? 'has-full' : '' ?>">Area penuh <strong><?= number_format($slotTersedia['motor']['area_penuh']) ?></strong> / <?= number_format($slotTersedia['motor']['jumlah_area']) ?></span>
                </div>
                <span class="parking-availability-progress"><span style="width: <?= $slotTersedia['motor']['kapasitas'] > 0 ? (int) round($slotTersedia['motor']['tersedia'] / $slotTersedia['motor']['kapasitas'] * 100) : 0 ?>%"></span></span>
            </div>
            <div class="parking-availability-item is-mobil">
                <div class="parking-availability-heading">
                    <span class="parking-availability-icon" aria-hidden="true">
                        <svg viewBox="0 0 32 32"><path d="M4 20v-6l3-6h16l5 6v6"/><circle cx="9" cy="21" r="3"/><circle cx="23" cy="21" r="3"/><path d="M4 16h24M9 8l-2 6h18l-2-6"/></svg>
                    </span>
                    <span class="parking-availability-type">Mobil</span>
                </div>
                <div class="parking-availability-count">
                    <strong><?= number_format($slotTersedia['mobil']['tersedia']) ?></strong>
                    <span>slot<br>tersedia</span>
                </div>
                <div class="parking-availability-meta">
                    <span>Terisi <strong><?= number_format($slotTersedia['mobil']['terisi']) ?></strong> / <?= number_format($slotTersedia['mobil']['kapasitas']) ?></span>
                    <span class="<?= $slotTersedia['mobil']['area_penuh'] > 0 ? 'has-full' : '' ?>">Area penuh <strong><?= number_format($slotTersedia['mobil']['area_penuh']) ?></strong> / <?= number_format($slotTersedia['mobil']['jumlah_area']) ?></span>
                </div>
                <span class="parking-availability-progress"><span style="width: <?= $slotTersedia['mobil']['kapasitas'] > 0 ? (int) round($slotTersedia['mobil']['tersedia'] / $slotTersedia['mobil']['kapasitas'] * 100) : 0 ?>%"></span></span>
            </div>
            <div class="parking-availability-item is-lainnya">
                <div class="parking-availability-heading">
                    <span class="parking-availability-icon" aria-hidden="true">
                        <svg viewBox="0 0 32 32"><path d="M5 10h15v13H5zM20 14h5l3 4v5h-8"/><circle cx="11" cy="24" r="3"/><circle cx="24" cy="24" r="3"/><path d="M8 14h8m-8 4h8"/></svg>
                    </span>
                    <span class="parking-availability-type">Lainnya</span>
                </div>
                <div class="parking-availability-count">
                    <strong><?= number_format($slotTersedia['lainnya']['tersedia']) ?></strong>
                    <span>slot<br>tersedia</span>
                </div>
                <div class="parking-availability-meta">
                    <span>Terisi <strong><?= number_format($slotTersedia['lainnya']['terisi']) ?></strong> / <?= number_format($slotTersedia['lainnya']['kapasitas']) ?></span>
                    <span class="<?= $slotTersedia['lainnya']['area_penuh'] > 0 ? 'has-full' : '' ?>">Area penuh <strong><?= number_format($slotTersedia['lainnya']['area_penuh']) ?></strong> / <?= number_format($slotTersedia['lainnya']['jumlah_area']) ?></span>
                </div>
                <span class="parking-availability-progress"><span style="width: <?= $slotTersedia['lainnya']['kapasitas'] > 0 ? (int) round($slotTersedia['lainnya']['tersedia'] / $slotTersedia['lainnya']['kapasitas'] * 100) : 0 ?>%"></span></span>
            </div>
        </div>

        <div class="hero-bottom">KETERSEDIAAN DIPERBARUI DARI DATA AREA</div>
    </section>

    <section class="login-content">
        <button class="theme-toggle theme-toggle-login" type="button" aria-pressed="false">Mode malam</button>
        <div class="login-form">
            <div class="login-mark">P</div>

            <div class="login-eyebrow">SELAMAT DATANG KEMBALI</div>
            <h2>Masuk ke akun Anda</h2>
            <p class="login-subtitle">
                Masukkan akun untuk membuka dashboard.
            </p>

            <?php if ($error !== ''): ?>
                <div class="login-alert">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="field">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Masukkan username"
                        value="<?= e($_POST['username'] ?? '') ?>"
                        autocomplete="username"
                        required
                        autofocus
                    >
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button class="login-submit" type="submit">
                    Masuk ke Dashboard <span>→</span>
                </button>
            </form>

            <div class="login-help">
                Akses khusus untuk Admin, Petugas, dan Owner terdaftar.
            </div>
        </div>
    </section>
</main>

</body>
</html>
