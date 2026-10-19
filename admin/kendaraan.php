
<?php

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/functions.php';

wajib_role(['admin']);

$msg = '';

/* PROSES TAMBAH DAN EDIT KENDARAAN */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'save') {

        $id = (int) ($_POST['id_kendaraan'] ?? 0);
        $plat = strtoupper(trim($_POST['plat_nomor'] ?? ''));
        $jenis = $_POST['jenis_kendaraan'] ?? '';
        $warna = trim($_POST['warna'] ?? '');
        $pemilik = trim($_POST['pemilik'] ?? '');
        $uid = (int) ($_POST['id_user'] ?? 0);

        if (
            $plat === '' ||
            !in_array($jenis, ['motor', 'mobil', 'lainnya'], true) ||
            $uid < 1
        ) {
            $msg = 'Lengkapi data kendaraan dengan benar.';
        } else {
            try {
                // Pastikan user yang dipilih tersedia
                $cekUser = $pdo->prepare(
                    'SELECT id_user FROM tb_user WHERE id_user = ?'
                );
                $cekUser->execute([$uid]);

                if (!$cekUser->fetch()) {
                    $msg = 'Pengguna yang dipilih tidak ditemukan.';
                } else {
                    if ($id > 0) {
                        $q = $pdo->prepare(
                            'UPDATE tb_kendaraan
                             SET plat_nomor = ?,
                                 jenis_kendaraan = ?,
                                 warna = ?,
                                 pemilik = ?,
                                 id_user = ?
                             WHERE id_kendaraan = ?'
                        );

                        $q->execute([
                            $plat,
                            $jenis,
                            $warna,
                            $pemilik,
                            $uid,
                            $id
                        ]);

                        catat_aktivitas(
                            $pdo,
                            'Mengedit data kendaraan: ' . $plat
                        );

                        $msg = 'Data kendaraan berhasil diperbarui.';
                    } else {
                        $q = $pdo->prepare(
                            'INSERT INTO tb_kendaraan
                             (plat_nomor, jenis_kendaraan, warna, pemilik, id_user)
                             VALUES (?, ?, ?, ?, ?)'
                        );

                        $q->execute([
                            $plat,
                            $jenis,
                            $warna,
                            $pemilik,
                            $uid
                        ]);

                        catat_aktivitas(
                            $pdo,
                            'Menambahkan kendaraan: ' . $plat
                        );

                        $msg = 'Data kendaraan berhasil ditambahkan.';
                    }
                }
            } catch (PDOException $e) {
                $msg = 'Gagal menyimpan data. Periksa plat nomor dan data kendaraan.';
            }
        }
    }

    /* PROSES HAPUS KENDARAAN */
    elseif ($action === 'delete') {

        $idHapus = (int) ($_POST['id'] ?? 0);

        try {
            $q = $pdo->prepare(
                'SELECT plat_nomor
                 FROM tb_kendaraan
                 WHERE id_kendaraan = ?'
            );
            $q->execute([$idHapus]);
            $kendaraan = $q->fetch(PDO::FETCH_ASSOC);

            if (!$kendaraan) {
                $msg = 'Data kendaraan tidak ditemukan.';
            } else {
                $q = $pdo->prepare(
                    'DELETE FROM tb_kendaraan WHERE id_kendaraan = ?'
                );
                $q->execute([$idHapus]);

                catat_aktivitas(
                    $pdo,
                    'Menghapus kendaraan: ' . $kendaraan['plat_nomor']
                );

                $msg = 'Data kendaraan berhasil dihapus.';
            }
        } catch (PDOException $e) {
            $msg = 'Kendaraan tidak dapat dihapus karena sudah digunakan dalam transaksi.';
        }
    }
}

/* AMBIL DATA UNTUK FORM EDIT */
$edit = null;

if (isset($_GET['edit'])) {
    $q = $pdo->prepare(
        'SELECT * FROM tb_kendaraan WHERE id_kendaraan = ?'
    );
    $q->execute([(int) $_GET['edit']]);
    $edit = $q->fetch(PDO::FETCH_ASSOC);
}

/* AMBIL DAFTAR USER */
$users = $pdo->query(
    'SELECT id_user, nama_lengkap
     FROM tb_user
     WHERE status_aktif = 1
     ORDER BY nama_lengkap'
)->fetchAll(PDO::FETCH_ASSOC);

/* AMBIL DAFTAR KENDARAAN */
$rows = $pdo->query(
    'SELECT k.*, u.nama_lengkap
     FROM tb_kendaraan k
     LEFT JOIN tb_user u ON u.id_user = k.id_user
     ORDER BY k.id_kendaraan DESC
     LIMIT 200'
)->fetchAll(PDO::FETCH_ASSOC);

$judul = 'Data Kendaraan';
$pageTitle = $judul;

require __DIR__ . '/../includes/header.php';

?>

<h1>Data Kendaraan</h1>

<?php if ($msg !== ''): ?>
    <div class="alert">
        <?= e($msg) ?>
    </div>
<?php endif; ?>

<div class="card crud-card">

    <h2>
        <?= $edit ? 'Edit Kendaraan' : 'Tambah Kendaraan' ?>
    </h2>

    <form method="post">

        <input type="hidden" name="action" value="save">

        <input
            type="hidden"
            name="id_kendaraan"
            value="<?= (int) ($edit['id_kendaraan'] ?? 0) ?>"
        >

        <div class="form-grid">

            <div>
                <label for="plat_nomor">Plat Nomor</label>
                <input
                    type="text"
                    id="plat_nomor"
                    name="plat_nomor"
                    placeholder="Contoh: B 1234 ABC"
                    value="<?= e($edit['plat_nomor'] ?? '') ?>"
                    required
                >
            </div>

            <div>
                <label for="jenis_kendaraan">Jenis Kendaraan</label>
                <select id="jenis_kendaraan" name="jenis_kendaraan" required>
                    <?php foreach (['motor', 'mobil', 'lainnya'] as $j): ?>
                        <option
                            value="<?= e($j) ?>"
                            <?= ($edit['jenis_kendaraan'] ?? 'motor') === $j
                                ? 'selected'
                                : '' ?>
                        >
                            <?= e(ucfirst($j)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="warna">Warna</label>
                <input
                    type="text"
                    id="warna"
                    name="warna"
                    placeholder="Masukkan warna kendaraan"
                    value="<?= e($edit['warna'] ?? '') ?>"
                >
            </div>

            <div>
                <label for="pemilik">Pemilik</label>
                <input
                    type="text"
                    id="pemilik"
                    name="pemilik"
                    placeholder="Nama pemilik kendaraan"
                    value="<?= e($edit['pemilik'] ?? '') ?>"
                >
            </div>

            <div>
                <label for="id_user">Dicatat Oleh User</label>

                <select name="id_user" id="id_user" required>
                    <?php foreach ($users as $u): ?>
                        <option
                            value="<?= (int) $u['id_user'] ?>"
                            <?= (int) ($edit['id_user'] ?? $_SESSION['id_user'])
                                === (int) $u['id_user']
                                ? 'selected'
                                : '' ?>
                        >
                            <?= e($u['nama_lengkap']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

        </div>

        <div class="crud-form-actions">
            <button class="crud-button crud-primary crud-save" type="submit">
                <?= $edit ? 'Simpan Perubahan' : 'Tambah Kendaraan' ?>
            </button>

            <a class="crud-button crud-neutral crud-cancel" href="kendaraan.php">
                Batal
            </a>
        </div>

    </form>
</div>

<div class="card crud-card">

    <h2>Daftar Kendaraan</h2>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Plat Nomor</th>
                    <th>Jenis</th>
                    <th>Warna</th>
                    <th>Pemilik</th>
                    <th>Dicatat Oleh</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center">
                            Belum ada data kendaraan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $i => $r): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= e($r['plat_nomor']) ?></strong></td>
                            <td><?= e(ucfirst($r['jenis_kendaraan'])) ?></td>
                            <td><?= e($r['warna']) ?></td>
                            <td><?= e($r['pemilik']) ?></td>
                            <td><?= e($r['nama_lengkap'] ?? '-') ?></td>
                            <td>
                                <div class="crud-actions">
                                    <a
                                        class="crud-button crud-neutral crud-edit"
                                        href="?edit=<?= (int) $r['id_kendaraan'] ?>"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="post"
                                        class="inline-form"
                                        onsubmit="return confirm('Yakin ingin menghapus kendaraan ini?')"
                                    >
                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $r['id_kendaraan'] ?>"
                                        >

                                        <button type="submit" class="crud-button crud-danger crud-delete">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
