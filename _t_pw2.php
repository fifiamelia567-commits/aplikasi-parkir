<?php
require_once __DIR__ . '/config/koneksi.php';
$out = [];
$cands = ['123456', 'admin123', 'petugas123', 'owner123', 'password', 'admin', '1234', '12345', 'parkirku'];
foreach ($pdo->query('SELECT username, password FROM tb_user ORDER BY id_user') as $r) {
    $h = (string) $r['password'];
    $match = 'NO-MATCH';
    foreach ($cands as $c) {
        if ($h === hash('sha256', $c)) {
            $match = $c . ' (sha256)';
            break;
        }
        if (password_verify($c, $h)) {
            $match = $c . ' (bcrypt)';
            break;
        }
    }
    $out[] = $r['username'] . ' -> ' . $match . ' | len=' . strlen($h) . ' | prefix=' . substr($h, 0, 12);
}
file_put_contents(__DIR__ . '/_pw.txt', implode(PHP_EOL, $out), LOCK_EX);
