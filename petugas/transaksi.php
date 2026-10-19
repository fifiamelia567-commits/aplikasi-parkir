<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/functions.php';
wajib_role(['petugas']);

$msg = '';
$tipeMsg = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'keluar') {
    $id = (int) ($_POST['id_parkir'] ?? 0);
    $q = $pdo->prepare(
        "SELECT tr.id_parkir, tr.waktu_masuk, tr.id_area, tf.tarif_per_jam
         FROM tb_transaksi tr
         JOIN tb_tarif tf ON tf.id_tarif = tr.id_tarif
         WHERE tr.id_parkir = ? AND tr.status = 'masuk'"
    );
    $q->execute([$id]);
    $tr = $q->fetch();

    if (!$tr) {
        $msg = 'Transaksi tidak ditemukan atau kendaraan sudah diproses keluar.';
    } else {
        $masuk = strtotime($tr['waktu_masuk']);

        if ($masuk === false) {
            $msg = 'Waktu masuk transaksi tidak valid.';
        } else {
            $durasi = max(1, (int) ceil((time() - $masuk) / 3600));
            $biaya = $durasi * (float) $tr['tarif_per_jam'];

            try {
                $pdo->beginTransaction();

                $q = $pdo->prepare(
                    'SELECT id_area
                     FROM tb_area_parkir
                     WHERE id_area = ?
                     FOR UPDATE'
                );
                $q->execute([$tr['id_area']]);

                if (!$q->fetch()) {
                    throw new RuntimeException('Area parkir transaksi tidak ditemukan.');
                }

                $q = $pdo->prepare(
                    "UPDATE tb_transaksi
                     SET waktu_keluar = NOW(), durasi_jam = ?, biaya_total = ?, status = 'keluar'
                     WHERE id_parkir = ? AND status = 'masuk'"
                );
                $q->execute([$durasi, $biaya, $id]);

                if ($q->rowCount() !== 1) {
                    throw new RuntimeException(
                        'Transaksi sudah diproses oleh petugas lain.'
                    );
                }

                $q = $pdo->prepare(
                    "SELECT COUNT(*)
                     FROM tb_transaksi
                     WHERE id_area = ? AND status = 'masuk'"
                );
                $q->execute([$tr['id_area']]);
                $terisi = (int) $q->fetchColumn();

                $q = $pdo->prepare(
                    'UPDATE tb_area_parkir SET terisi = ? WHERE id_area = ?'
                );
                $q->execute([$terisi, $tr['id_area']]);

                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log('Kesalahan transaksi kendaraan keluar: ' . $e->getMessage());
                $msg = $e instanceof RuntimeException
                    ? $e->getMessage()
                    : 'Transaksi keluar gagal disimpan. Periksa log error PHP.';
            }

            if ($msg === '') {
                catat_aktivitas($pdo, 'Memproses kendaraan keluar');
                header('Location: struk.php?id=' . $id);
                exit;
            }
        }
    }
}

$rows = $pdo->query(
    "SELECT tr.id_parkir, k.plat_nomor, k.jenis_kendaraan,
            tr.waktu_masuk, a.nama_area
     FROM tb_transaksi tr
     JOIN tb_kendaraan k ON k.id_kendaraan = tr.id_kendaraan
     JOIN tb_area_parkir a ON a.id_area = tr.id_area
     WHERE tr.status = 'masuk'
     ORDER BY tr.waktu_masuk DESC
     LIMIT 200"
)->fetchAll();

$pageTitle = 'Transaksi Parkir';
require __DIR__ . '/../includes/header.php';
?>
<h1>Transaksi Parkir</h1>
<?php if ($msg !== ''): ?>
    <div class="alert error"><?= e($msg) ?></div>
<?php endif; ?>
<div class="card">
    <p><a class="btn" href="masuk.php">+ Kendaraan Masuk</a></p>
    <h2>Kendaraan yang masih parkir</h2>
    <div class="table-wrap">
        <table>
            <tr>
                <th>No. Parkir</th>
                <th>Plat</th>
                <th>Jenis</th>
                <th>Area</th>
                <th>Waktu Masuk</th>
                <th>Aksi</th>
            </tr>
            <?php foreach ($rows as $r): ?>
                <?php
                $jenisKendaraan = strtolower((string) $r['jenis_kendaraan']);
                $kelasJenis = $jenisKendaraan === 'motor'
                    ? 'is-motor'
                    : ($jenisKendaraan === 'mobil' ? 'is-mobil' : 'is-other');
                ?>
                <tr>
                    <td><?= (int) $r['id_parkir'] ?></td>
                    <td><?= e($r['plat_nomor']) ?></td>
                    <td>
                        <span class="vehicle-type-badge <?= $kelasJenis ?>">
                            <?php if ($jenisKendaraan === 'motor'): ?>
                                <svg viewBox="0 0 32 32" aria-hidden="true"><circle cx="8" cy="23" r="4"/><circle cx="25" cy="23" r="4"/><path d="m8 23 6-10h6l5 10m-11-10-3-3m3 3 5 10h6M18 9h4"/></svg>
                            <?php elseif ($jenisKendaraan === 'mobil'): ?>
                                <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M4 20v-6l3-6h16l5 6v6"/><circle cx="9" cy="21" r="3"/><circle cx="23" cy="21" r="3"/><path d="M4 16h24M9 8l-2 6h18l-2-6"/></svg>
                            <?php else: ?>
                                <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M5 10h15v13H5zM20 14h5l3 4v5h-8"/><circle cx="11" cy="24" r="3"/><circle cx="24" cy="24" r="3"/><path d="M8 14h8m-8 4h8"/></svg>
                            <?php endif; ?>
                            <?= e(ucfirst((string) $r['jenis_kendaraan'])) ?>
                        </span>
                    </td>
                    <td><?= e($r['nama_area']) ?></td>
                    <td><?= e($r['waktu_masuk']) ?></td>
                    <td>
                        <form method="post" onsubmit="return confirm('Proses kendaraan keluar dan hitung biaya?')">
                            <input type="hidden" name="action" value="keluar">
                            <input type="hidden" name="id_parkir" value="<?= (int) $r['id_parkir'] ?>">
                            <button type="submit">Keluar &amp; Cetak Struk</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
