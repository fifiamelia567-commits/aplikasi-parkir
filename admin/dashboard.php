<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/functions.php';
wajib_role(['admin']);

$users = (int) $pdo->query('SELECT COUNT(*) FROM tb_user')->fetchColumn();
$kendaraan = (int) $pdo->query('SELECT COUNT(*) FROM tb_kendaraan')->fetchColumn();
$aktif = (int) $pdo->query(
    "SELECT COUNT(*) FROM tb_transaksi WHERE status = 'masuk'"
)->fetchColumn();
$pendapatan = (float) $pdo->query(
    "SELECT COALESCE(SUM(biaya_total), 0)
     FROM tb_transaksi WHERE status = 'keluar'"
)->fetchColumn();

$today = new DateTimeImmutable('today');
$startDate = $today->modify('-6 days');
$chartStart = $startDate->format('Y-m-d');
$chartEnd = $today->format('Y-m-d');
$q = $pdo->prepare(
    "SELECT DATE(waktu_keluar) AS tanggal, SUM(biaya_total) AS total
     FROM tb_transaksi
     WHERE status = 'keluar' AND DATE(waktu_keluar) BETWEEN ? AND ?
     GROUP BY DATE(waktu_keluar)"
);
$q->execute([$chartStart, $chartEnd]);
$dailyRevenue = [];
foreach ($q->fetchAll() as $row) {
    $dailyRevenue[$row['tanggal']] = (float) $row['total'];
}

$chartDays = [];
$maxDailyRevenue = 0;
for ($offset = 0; $offset < 7; $offset++) {
    $day = $startDate->modify('+' . $offset . ' days');
    $date = $day->format('Y-m-d');
    $amount = $dailyRevenue[$date] ?? 0;
    $chartDays[] = [
        'date' => $date,
        'label' => $day->format('d/m'),
        'amount' => $amount,
    ];
    $maxDailyRevenue = max($maxDailyRevenue, $amount);
}
$maxDailyRevenue = max(1, $maxDailyRevenue);

$areas = $pdo->query(
    "SELECT a.nama_area, a.kapasitas,
            COALESCE(parkir.terisi, 0) AS terisi
     FROM tb_area_parkir a
     LEFT JOIN (
         SELECT id_area, COUNT(*) AS terisi
         FROM tb_transaksi
         WHERE status = 'masuk'
         GROUP BY id_area
     ) parkir ON parkir.id_area = a.id_area
     ORDER BY a.nama_area"
)->fetchAll();
$capacityWarnings = [];
foreach ($areas as $area) {
    $capacity = (int) $area['kapasitas'];
    $occupied = (int) $area['terisi'];
    if ($capacity > 0 && $occupied / $capacity >= 0.85) {
        $capacityWarnings[] = [
            'name' => $area['nama_area'],
            'occupied' => $occupied,
            'capacity' => $capacity,
            'full' => $occupied >= $capacity,
        ];
    }
}
$recent = $pdo->query(
    'SELECT tr.id_parkir, tr.waktu_masuk, tr.status, k.plat_nomor,
            k.jenis_kendaraan
     FROM tb_transaksi tr
     JOIN tb_kendaraan k ON k.id_kendaraan = tr.id_kendaraan
     ORDER BY tr.id_parkir DESC
     LIMIT 6'
)->fetchAll();

$pageTitle = 'Dashboard Admin';
require __DIR__ . '/../includes/header.php';
?>
<section class="card dashboard-intro">
    <div>
        <h1>Ringkasan Operasional</h1>
        <p>Pantau aktivitas dan kapasitas parkir hari ini.</p>
    </div>
    <div class="dashboard-intro-date"><?= e($today->format('d/m/Y')) ?></div>
</section>

<?php if ($capacityWarnings): ?>
    <section class="capacity-alert <?= count(array_filter($capacityWarnings, static fn ($area) => $area['full'])) ? 'is-critical' : '' ?>" role="status">
        <span class="capacity-alert-icon" aria-hidden="true">!</span>
        <div>
            <strong><?= count(array_filter($capacityWarnings, static fn ($area) => $area['full'])) ? 'Ada area parkir penuh' : 'Kapasitas parkir hampir penuh' ?></strong>
            <p>
                <?php foreach ($capacityWarnings as $index => $area): ?>
                    <?= $index > 0 ? ' · ' : '' ?><?= e($area['name']) ?>:
                    <?= $area['full'] ? 'penuh' : 'tersisa ' . number_format($area['capacity'] - $area['occupied']) . ' slot' ?>
                    (<?= number_format((int) round($area['occupied'] / $area['capacity'] * 100)) ?>%)
                <?php endforeach; ?>
            </p>
        </div>
    </section>
<?php endif; ?>

<div class="cards dashboard-stats">
    <section class="card dashboard-stat">
        <div class="dashboard-stat-label">Total Pengguna</div>
        <div class="stat"><?= number_format($users) ?></div>
    </section>
    <section class="card dashboard-stat">
        <div class="dashboard-stat-label">Data Kendaraan</div>
        <div class="stat"><?= number_format($kendaraan) ?></div>
    </section>
    <section class="card dashboard-stat">
        <div class="dashboard-stat-label">Sedang Parkir</div>
        <div class="stat"><?= number_format($aktif) ?></div>
    </section>
    <section class="card dashboard-stat">
        <div class="dashboard-stat-label">Total Pendapatan</div>
        <div class="stat"><?= rupiah($pendapatan) ?></div>
    </section>
</div>

<div class="dashboard-panels">
    <section class="card dashboard-panel">
        <h2>Pendapatan Harian</h2>
        <p class="dashboard-panel-subtitle">Pergerakan pendapatan selama 7 hari terakhir</p>
        <div class="revenue-chart" role="img" aria-label="Diagram pendapatan harian selama tujuh hari">
            <?php foreach ($chartDays as $day): ?>
                <?php $height = $day['amount'] > 0 ? max(5, (int) round($day['amount'] / $maxDailyRevenue * 100)) : 0; ?>
                <div class="revenue-day">
                    <div class="revenue-bar-track">
                        <div
                            class="revenue-bar"
                            style="height: <?= $height ?>%"
                            title="<?= e($day['label'] . ': ' . rupiah($day['amount'])) ?>"
                        ></div>
                    </div>
                    <span class="revenue-day-label"><?= e($day['label']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="dashboard-legend">Pendapatan selesai</div>
    </section>

    <section class="card dashboard-panel">
        <h2>Kapasitas Area</h2>
        <p class="dashboard-panel-subtitle">Pemakaian tempat parkir saat ini</p>
        <?php if ($areas): ?>
            <div class="parking-area-list">
                <?php foreach ($areas as $area): ?>
                    <?php
                    $capacity = (int) $area['kapasitas'];
                    $occupied = (int) $area['terisi'];
                    $percent = $capacity > 0
                        ? min(100, max(0, (int) round($occupied / $capacity * 100)))
                        : 0;
                    ?>
                    <div>
                        <div class="parking-area-header">
                            <strong><?= e($area['nama_area']) ?></strong>
                            <span><?= number_format($occupied) ?> / <?= number_format($capacity) ?></span>
                        </div>
                        <div
                            class="parking-progress"
                            role="progressbar"
                            aria-label="<?= e($area['nama_area']) ?>"
                            aria-valuemin="0"
                            aria-valuemax="<?= $capacity ?>"
                            aria-valuenow="<?= min($capacity, max(0, $occupied)) ?>"
                        >
                            <span class="<?= $percent >= 85 ? 'is-busy' : '' ?>" style="width: <?= $percent ?>%"></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="dashboard-empty">Belum ada area parkir. Tambahkan area melalui menu Area Parkir.</div>
        <?php endif; ?>
    </section>
</div>

<section class="card dashboard-table-card">
    <div class="dashboard-table-heading">
        <h2>Transaksi Terbaru</h2>
        <p>Aktivitas parkir terakhir yang tercatat</p>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No. Parkir</th>
                    <th>Plat Nomor</th>
                    <th>Jenis</th>
                    <th>Waktu Masuk</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$recent): ?>
                    <tr><td colspan="5" class="dashboard-empty">Belum ada transaksi parkir.</td></tr>
                <?php else: ?>
                    <?php foreach ($recent as $transaction): ?>
                        <tr>
                            <td>#<?= (int) $transaction['id_parkir'] ?></td>
                            <td><strong><?= e($transaction['plat_nomor']) ?></strong></td>
                            <td><?= e(ucfirst($transaction['jenis_kendaraan'])) ?></td>
                            <td><?= e($transaction['waktu_masuk']) ?></td>
                            <td>
                                <span class="dashboard-status <?= $transaction['status'] === 'keluar' ? 'is-complete' : '' ?>">
                                    <?= $transaction['status'] === 'keluar' ? 'Selesai' : 'Sedang parkir' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
