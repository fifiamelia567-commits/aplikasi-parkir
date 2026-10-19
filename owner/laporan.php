<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/functions.php';
wajib_role(['owner']);

$awal = $_GET['awal'] ?? date('Y-m-01');
$akhir = $_GET['akhir'] ?? date('Y-m-d');
$validDate = static function (string $date): bool {
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    return $parsed !== false && $parsed->format('Y-m-d') === $date;
};

if (!$validDate($awal) || !$validDate($akhir) || $awal > $akhir) {
    $awal = date('Y-m-01');
    $akhir = date('Y-m-d');
}

$q = $pdo->prepare(
    "SELECT tr.id_parkir, k.plat_nomor, k.jenis_kendaraan,
            tr.waktu_masuk, tr.waktu_keluar, tr.durasi_jam,
            tr.biaya_total, u.nama_lengkap AS petugas
     FROM tb_transaksi tr
     JOIN tb_kendaraan k ON k.id_kendaraan = tr.id_kendaraan
     JOIN tb_user u ON u.id_user = tr.id_user
     WHERE tr.status = 'keluar' AND DATE(tr.waktu_keluar) BETWEEN ? AND ?
     ORDER BY tr.waktu_keluar DESC
     LIMIT 1000"
);
$q->execute([$awal, $akhir]);
$rows = $q->fetchAll();
$total = 0;
foreach ($rows as $row) {
    $total += (float) $row['biaya_total'];
}

if (($_GET['format'] ?? '') === 'csv') {
    $safeDate = static function (string $value): string {
        return preg_match('/^[=+\-@]/', $value) ? "'" . $value : $value;
    };
    $filename = 'laporan-parkir-' . $awal . '-sampai-' . $akhir . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('X-Content-Type-Options: nosniff');

    $output = fopen('php://output', 'wb');
    if ($output === false) {
        throw new RuntimeException('Gagal membuka output ekspor CSV.');
    }

    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, [
        'No. Parkir',
        'Plat Nomor',
        'Jenis Kendaraan',
        'Waktu Masuk',
        'Waktu Keluar',
        'Durasi (jam)',
        'Biaya (Rp)',
        'Petugas',
    ]);

    foreach ($rows as $row) {
        fputcsv($output, [
            (int) $row['id_parkir'],
            $safeDate((string) $row['plat_nomor']),
            $safeDate((string) $row['jenis_kendaraan']),
            $safeDate((string) $row['waktu_masuk']),
            $safeDate((string) $row['waktu_keluar']),
            (int) $row['durasi_jam'],
            (float) $row['biaya_total'],
            $safeDate((string) $row['petugas']),
        ]);
    }

    fclose($output);
    exit;
}

$pageTitle = 'Rekap Transaksi';
require __DIR__ . '/../includes/header.php';
?>
<h1>Rekap Transaksi</h1>
<section class="card report-filters no-print">
    <form method="get" class="form-grid">
        <div>
            <label for="awal">Dari tanggal</label>
            <input id="awal" type="date" name="awal" value="<?= e($awal) ?>" required>
        </div>
        <div>
            <label for="akhir">Sampai tanggal</label>
            <input id="akhir" type="date" name="akhir" value="<?= e($akhir) ?>" required>
        </div>
        <div class="report-actions">
            <button type="submit">Tampilkan</button>
            <button type="button" class="btn secondary" onclick="window.print()">Cetak Laporan</button>
            <button type="submit" name="format" value="csv" class="btn report-export">Ekspor CSV</button>
        </div>
    </form>
</section>

<section class="card report-results">
    <h2>Total pendapatan: <?= rupiah($total) ?></h2>
    <p class="muted">Periode <?= e($awal) ?> sampai <?= e($akhir) ?>. Menampilkan maksimal 1.000 transaksi.</p>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Plat</th>
                    <th>Jenis</th>
                    <th>Masuk</th>
                    <th>Keluar</th>
                    <th>Durasi</th>
                    <th>Biaya</th>
                    <th>Petugas</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="8" class="dashboard-empty">Tidak ada transaksi selesai pada periode ini.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= (int) $row['id_parkir'] ?></td>
                            <td><?= e($row['plat_nomor']) ?></td>
                            <td><?= e(ucfirst($row['jenis_kendaraan'])) ?></td>
                            <td><?= e($row['waktu_masuk']) ?></td>
                            <td><?= e($row['waktu_keluar']) ?></td>
                            <td><?= (int) $row['durasi_jam'] ?> jam</td>
                            <td><?= rupiah($row['biaya_total']) ?></td>
                            <td><?= e($row['petugas']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
