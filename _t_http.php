<?php
$base = 'http://127.0.0.1:8899/aplikasi-parkir';
function mkjar() { return tempnam(sys_get_temp_dir(), 'jar'); }
function req($url, $method = 'GET', $data = null, $jar = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 20,
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $loc = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    return [$code, $loc, $body];
}

$users = [
    'admin'   => ['admin1', '123456', ['/admin/dashboard.php','/admin/users.php','/admin/tarif.php','/admin/area.php','/admin/kendaraan.php','/admin/log.php','/admin/backup.php']],
    'petugas' => ['petugas1', '123456', ['/petugas/masuk.php','/petugas/transaksi.php']],
    'owner'   => ['owner1', '123456', ['/owner/dashboard.php','/owner/laporan.php']],
];
$fail = 0; $pass = 0;
foreach ($users as $role => [$u, $p, $pages]) {
    $jar = mkjar();
    [$c, $l] = req($base . '/auth/login.php', 'GET', null, $jar);
    [$c, $l] = req($base . '/auth/login.php', 'POST', ['username' => $u, 'password' => $p], $jar);
    echo "LOGIN $u => $c " . ($l ?: '') . PHP_EOL;
    if ($c !== 302) { $fail++; echo "  !! LOGIN GAGAL" . PHP_EOL; continue; }
    foreach ($pages as $pg) {
        [$c, $l, $b] = req($base . $pg, 'GET', null, $jar);
        $hasCss = strpos($b, 'style.css') !== false;
        $errs = preg_match('/Fatal error|Parse error|Warning:|Notice:|Uncaught/i', $b);
        $ok = ($c === 200 && $hasCss && !$errs);
        $ok ? $pass++ : $fail++;
        echo sprintf("  %-26s %s len=%-6d css=%s err=%s %s%s%s", $pg, $c, strlen($b),
            $hasCss ? 'Y' : 'N', $errs ? 'YES' : 'no', $ok ? '==> OK' : '==> CHECK',
            $l ? " redir=$l" : '', PHP_EOL);
    }
}
echo PHP_EOL . "PASS=$pass FAIL=$fail" . PHP_EOL;