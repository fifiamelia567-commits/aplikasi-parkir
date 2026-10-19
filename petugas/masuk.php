
<?php

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/functions.php';

wajib_role(['petugas']);

$msg = '';
$tipeMsg = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $kid = (int) ($_POST['id_kendaraan'] ?? 0);
    $area = (int) ($_POST['id_area'] ?? 0);
    $idPetugas = (int) ($_SESSION['id_user'] ?? 0);

    if ($kid < 1 || $area < 1 || $idPetugas < 1) {
        $msg = 'Pilih kendaraan dan area parkir yang valid.';
        $tipeMsg = 'error';
    } else {
        try {
            // Periksa kendaraan yang masih berada di area parkir
            $q = $pdo->prepare(
                "SELECT id_parkir
                 FROM tb_transaksi
                 WHERE id_kendaraan = ? AND status = 'masuk'
                 LIMIT 1"
            );
            $q->execute([$kid]);

            if ($q->fetch()) {
                $msg = 'Kendaraan ini masih tercatat berada di area parkir.';
                $tipeMsg = 'error';
            } else {
                // Ambil tarif sesuai jenis kendaraan
                $q = $pdo->prepare(
                    'SELECT k.jenis_kendaraan, t.id_tarif
                     FROM tb_kendaraan k
                     JOIN tb_tarif t
                       ON t.jenis_kendaraan = k.jenis_kendaraan
                     WHERE k.id_kendaraan = ?
                     LIMIT 1'
                );
                $q->execute([$kid]);
                $data = $q->fetch(PDO::FETCH_ASSOC);

                if (!$data) {
                    $msg = 'Tarif untuk jenis kendaraan ini belum tersedia.';
                    $tipeMsg = 'error';
                } else {
                    // Mulai transaksi database
                    $pdo->beginTransaction();

                    // Kunci area agar kapasitas tidak terlampaui
                    $q = $pdo->prepare(
                        "SELECT kapasitas,
                                CASE
                                    WHEN LOWER(nama_area) LIKE '%motor%' THEN 'motor'
                                    WHEN LOWER(nama_area) LIKE '%mobil%' THEN 'mobil'
                                    ELSE 'lainnya'
                                END AS kategori
                         FROM tb_area_parkir
                         WHERE id_area = ?
                         FOR UPDATE"
                    );
                    $q->execute([$area]);
                    $a = $q->fetch(PDO::FETCH_ASSOC);

                    if (!$a) {
                        throw new RuntimeException(
                            'Area parkir tidak ditemukan.'
                        );
                    }

                    if ($a['kategori'] !== $data['jenis_kendaraan']) {
                        throw new RuntimeException(
                            'Jenis kendaraan tidak sesuai dengan kategori area parkir yang dipilih.'
                        );
                    }

                    $q = $pdo->prepare(
                        "SELECT COUNT(*)
                         FROM tb_transaksi
                         WHERE id_area = ? AND status = 'masuk'"
                    );
                    $q->execute([$area]);
                    $terisi = (int) $q->fetchColumn();

                    if ($terisi >= (int) $a['kapasitas']) {
                        throw new RuntimeException(
                            'Area parkir sudah penuh.'
                        );
                    }

                    // Simpan transaksi kendaraan masuk
                    $q = $pdo->prepare(
                        "INSERT INTO tb_transaksi
                         (id_kendaraan, waktu_masuk, id_tarif,
                          status, id_user, id_area)
                         VALUES (?, NOW(), ?, 'masuk', ?, ?)"
                    );

                    $q->execute([
                        $kid,
                        $data['id_tarif'],
                        $idPetugas,
                        $area
                    ]);

                    // Tambahkan jumlah kendaraan di area
                    $q = $pdo->prepare(
                        'UPDATE tb_area_parkir
                         SET terisi = ?
                         WHERE id_area = ?'
                    );
                    $q->execute([$terisi + 1, $area]);

                    $pdo->commit();

                    catat_aktivitas(
                        $pdo,
                        'Mencatat kendaraan masuk'
                    );

                    $msg = 'Kendaraan masuk berhasil dicatat.';
                    $tipeMsg = 'success';
                }
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // Tampilkan penyebab sebenarnya pada log server
            error_log(
                'Kesalahan transaksi kendaraan masuk: ' . $e->getMessage()
            );

            if ($e instanceof RuntimeException) {
                $msg = $e->getMessage();
            } else {
                $msg = 'Transaksi gagal disimpan. Periksa struktur tabel dan log error PHP.';
            }

            $tipeMsg = 'error';
        }
    }
}

// Ambil daftar kendaraan
$kendaraan = $pdo->query(
    'SELECT id_kendaraan, plat_nomor, jenis_kendaraan
     FROM tb_kendaraan
     ORDER BY plat_nomor'
)->fetchAll(PDO::FETCH_ASSOC);

// Ambil daftar area parkir
$areas = $pdo->query(
    "SELECT a.id_area, a.nama_area, a.kapasitas,
            COALESCE(parkir.terisi, 0) AS terisi,
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
     ORDER BY nama_area"
)->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Kendaraan Masuk';

require __DIR__ . '/../includes/header.php';

?>

<h1>Kendaraan Masuk</h1>

<?php if ($msg !== ''): ?>
    <div class="alert <?= $tipeMsg === 'error' ? 'error' : '' ?>">
        <?= e($msg) ?>
    </div>
<?php endif; ?>

<div class="card crud-card">
    <h2>Form Kendaraan Masuk</h2>

    <form method="post">

        <div class="field">
            <label for="id_kendaraan">Kendaraan</label>

            <select name="id_kendaraan" id="id_kendaraan" required>
                <option value="">-- Pilih kendaraan --</option>

                <?php foreach ($kendaraan as $k): ?>
                    <option value="<?= (int) $k['id_kendaraan'] ?>" data-category="<?= e($k['jenis_kendaraan']) ?>">
                        <?= e(
                            $k['plat_nomor'] . ' - ' .
                            ucfirst($k['jenis_kendaraan'])
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="id_area">Area Parkir</label>

            <select name="id_area" id="id_area" required>
                <option value="">-- Pilih area --</option>

                <?php foreach ($areas as $a): ?>
                    <option
                        value="<?= (int) $a['id_area'] ?>"
                        data-category="<?= e($a['kategori']) ?>"
                        <?= (int) $a['terisi'] >= (int) $a['kapasitas'] ? 'disabled' : '' ?>
                    >
                        <?= e($a['nama_area']) ?>
                        (tersisa:
                        <?= max(
                            0,
                            (int) $a['kapasitas'] - (int) $a['terisi']
                        ) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="crud-form-actions">
        <button class="crud-button crud-primary crud-save" type="submit">
            Catat Kendaraan Masuk
        </button>
        </div>

    </form>
</div>

<script>
    const vehicleSelect = document.getElementById('id_kendaraan');
    const areaSelect = document.getElementById('id_area');
    const areaOptions = Array.from(areaSelect.options).slice(1);

    const filterAreasByVehicle = () => {
        const vehicleCategory = vehicleSelect.selectedOptions[0]?.dataset.category || '';

        areaOptions.forEach((option) => {
            const isFull = option.dataset.full === 'true';
            option.hidden = vehicleCategory !== '' && option.dataset.category !== vehicleCategory;
            option.disabled = isFull || (vehicleCategory !== '' && option.dataset.category !== vehicleCategory);
        });

        if (areaSelect.selectedOptions[0]?.disabled) {
            areaSelect.value = '';
        }
    };

    areaOptions.forEach((option) => {
        option.dataset.full = String(option.disabled);
    });
    vehicleSelect.addEventListener('change', filterAreasByVehicle);
    filterAreasByVehicle();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
