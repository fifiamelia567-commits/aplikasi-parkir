<?php
require_once __DIR__ . '/config/koneksi.php';
$out = [];
$out[] = 'DB: ' . $pdo->query('SELECT DATABASE()')->fetchColumn();
foreach ($pdo->query('SHOW TABLES') as $r) {
    $out[] = 'TABLE: ' . $r[0];
}
foreach ($pdo->query('SELECT id_user, nama_lengkap, username, role, status_aktif FROM tb_user ORDER BY id_user') as $r) {
    $out[] = 'USER: ' . implode(' | ', array_slice($r, 0, 5));
}
$out[] = 'Transaksi: ' . $pdo->query('SELECT COUNT(*) FROM tb_transaksi')->fetchColumn();
$out[] = 'Tarif: ';
foreach ($pdo->query('SELECT jenis_kendaraan, tarif_per_jam FROM tb_tarif ORDER BY id_tarif') as $r) {
    $out[] = '  ' . $r['jenis_kendaraan'] . ' = ' . $r['tarif_per_jam'];
}
file_put_contents(__DIR__ . '/_dbinfo.txt', implode(PHP_EOL, $out), LOCK_EX);
