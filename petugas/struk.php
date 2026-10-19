<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/functions.php';
wajib_role(['petugas']);

$id = (int) ($_GET['id'] ?? 0);
$q = $pdo->prepare(
    'SELECT tr.*, k.plat_nomor, k.jenis_kendaraan, k.pemilik,
            a.nama_area, tf.tarif_per_jam, u.nama_lengkap AS petugas
     FROM tb_transaksi tr
     JOIN tb_kendaraan k ON k.id_kendaraan = tr.id_kendaraan
     JOIN tb_area_parkir a ON a.id_area = tr.id_area
     JOIN tb_tarif tf ON tf.id_tarif = tr.id_tarif
     JOIN tb_user u ON u.id_user = tr.id_user
     WHERE tr.id_parkir = ?'
);
$q->execute([$id]);
$r = $q->fetch();

if (!$r) {
    http_response_code(404);
    exit('Data transaksi tidak ditemukan.');
}

$barcodeValue = 'PARKIR-' . str_pad((string) $r['id_parkir'], 6, '0', STR_PAD_LEFT);
$code39Patterns = [
    '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw',
    '3' => 'wnwwnnnnn', '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn',
    '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw', '8' => 'wnnwnnwnn',
    '9' => 'nnwwnnwnn', 'A' => 'wnnnnwnnw', 'B' => 'nnwnnwnnw',
    'C' => 'wnwnnwnnn', 'D' => 'nnnnwwnnw', 'E' => 'wnnnwwnnn',
    'F' => 'nnwnwwnnn', 'G' => 'nnnnnwwnw', 'H' => 'wnnnnwwnn',
    'I' => 'nnwnnwwnn', 'J' => 'nnnnwwwnn', 'K' => 'wnnnnnnww',
    'L' => 'nnwnnnnww', 'M' => 'wnwnnnnwn', 'N' => 'nnnnwnnww',
    'O' => 'wnnnwnnwn', 'P' => 'nnwnwnnwn', 'Q' => 'nnnnnnwww',
    'R' => 'wnnnnnwwn', 'S' => 'nnwnnnwwn', 'T' => 'nnnnwnwwn',
    'U' => 'wwnnnnnnw', 'V' => 'nwwnnnnnw', 'W' => 'wwwnnnnnn',
    'X' => 'nwnnwnnnw', 'Y' => 'wwnnwnnnn', 'Z' => 'nwwnwnnnn',
    '-' => 'nwnnnnwnw', '*' => 'nwnnwnwnn',
];
$barcodeText = '*' . $barcodeValue . '*';
$narrow = 2;
$wide = 5;
$barHeight = 56;
$barcodeWidth = 0;
foreach (str_split($barcodeText) as $index => $character) {
    if (!isset($code39Patterns[$character])) {
        throw new RuntimeException('Karakter barcode tidak didukung.');
    }

    foreach (str_split($code39Patterns[$character]) as $patternIndex => $size) {
        $barcodeWidth += $size === 'w' ? $wide : $narrow;
    }

    if ($index < strlen($barcodeText) - 1) {
        $barcodeWidth += $narrow;
    }
}

$barcodeX = 0;
$barcodeBars = [];
foreach (str_split($barcodeText) as $characterIndex => $character) {
    foreach (str_split($code39Patterns[$character]) as $patternIndex => $size) {
        $elementWidth = $size === 'w' ? $wide : $narrow;

        if ($patternIndex % 2 === 0) {
            $barcodeBars[] = '<rect x="' . $barcodeX . '" y="0" width="' .
                $elementWidth . '" height="' . $barHeight . '"/>';
        }

        $barcodeX += $elementWidth;
    }

    if ($characterIndex < strlen($barcodeText) - 1) {
        $barcodeX += $narrow;
    }
}

$pageTitle = 'Struk Parkir';
require __DIR__ . '/../includes/header.php';
?>
<section class="receipt-page">
    <article class="receipt">
        <header class="receipt-header">
            <div class="receipt-brand-mark" aria-hidden="true">P</div>
            <div class="receipt-brand">APLIKASI PARKIR</div>
            <h1>BUKTI PARKIR</h1>
            <p>Terima kasih telah menggunakan layanan kami</p>
        </header>

        <div class="receipt-ticket">
            <span class="receipt-label">Nomor tiket</span>
            <strong>#<?= (int) $r['id_parkir'] ?></strong>
            <span class="receipt-status <?= $r['status'] === 'keluar' ? 'is-complete' : '' ?>">
                <?= $r['status'] === 'keluar' ? 'SELESAI' : 'SEDANG PARKIR' ?>
            </span>
        </div>

        <section class="receipt-section">
            <h2>Informasi kendaraan</h2>
            <dl class="receipt-details">
                <div><dt>Nomor polisi</dt><dd><?= e($r['plat_nomor']) ?></dd></div>
                <div><dt>Jenis kendaraan</dt><dd><?= e(ucfirst($r['jenis_kendaraan'])) ?></dd></div>
                <div><dt>Pemilik</dt><dd><?= e($r['pemilik'] ?: '-') ?></dd></div>
                <div><dt>Area parkir</dt><dd><?= e($r['nama_area']) ?></dd></div>
            </dl>
        </section>

        <section class="receipt-section">
            <h2>Rincian waktu</h2>
            <dl class="receipt-details">
                <div><dt>Waktu masuk</dt><dd><?= e($r['waktu_masuk']) ?></dd></div>
                <div><dt>Waktu keluar</dt><dd><?= e($r['waktu_keluar'] ?? 'Belum keluar') ?></dd></div>
                <div><dt>Durasi</dt><dd><?= e($r['durasi_jam'] ?? '-') ?><?= $r['durasi_jam'] !== null ? ' jam' : '' ?></dd></div>
                <div><dt>Tarif per jam</dt><dd><?= rupiah($r['tarif_per_jam']) ?></dd></div>
            </dl>
        </section>

        <div class="receipt-total">
            <span><?= $r['status'] === 'keluar' ? 'Total pembayaran' : 'Perkiraan biaya' ?></span>
            <strong><?= $r['status'] === 'keluar' ? rupiah($r['biaya_total']) : 'Dihitung saat keluar' ?></strong>
        </div>

        <section class="receipt-barcode" aria-label="Barcode tiket parkir">
            <svg
                class="receipt-barcode-svg"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 <?= $barcodeWidth ?> <?= $barHeight ?>"
                role="img"
                aria-label="Barcode Code 39 <?= e($barcodeValue) ?>"
                shape-rendering="crispEdges"
            >
                <rect width="<?= $barcodeWidth ?>" height="<?= $barHeight ?>" fill="#fff"/>
                <g fill="#111"><?= implode('', $barcodeBars) ?></g>
            </svg>
            <div class="receipt-barcode-value"><?= e($barcodeValue) ?></div>
            <small>Barcode Code 39 · Nomor tiket</small>
        </section>

        <footer class="receipt-footer">
            <div>Petugas: <strong><?= e($r['petugas']) ?></strong></div>
            <p>Simpan struk ini sebagai bukti parkir.</p>
        </footer>
    </article>

    <div class="receipt-actions no-print">
        <button type="button" onclick="window.print()">Cetak Struk</button>
        <a class="btn receipt-back" href="transaksi.php">Kembali ke Transaksi</a>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
