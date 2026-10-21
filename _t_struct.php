<?php
$base = 'http://127.0.0.1:8899/aplikasi-parkir';
$jar = tempnam(sys_get_temp_dir(), 'jar');
function req($url, $method = 'GET', $data = null, $jar = null) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>false,
        CURLOPT_COOKIEJAR=>$jar, CURLOPT_COOKIEFILE=>$jar, CURLOPT_TIMEOUT=>20]);
    if ($method === 'POST') { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); }
    $b = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$c, $b];
}
req($base.'/auth/login.php','GET',null,$jar);
req($base.'/auth/login.php','POST',['username'=>'admin1','password'=>'123456'],$jar);
[$c,$html] = req($base.'/admin/dashboard.php','GET',null,$jar);

echo "=== struktur yang WAJIB ada untuk layout baru ===" . PHP_EOL;
$required = [
  'topbar' => 1, 'brand' => 1, 'brand-icon' => 1, 'userbox' => 1, 'theme-toggle' => 1,
  'layout' => 1, 'sidebar' => 1, 'sidebar-heading' => 1, 'content' => 1, 'footer' => 1,
  'feature-illustration' => 1, 'dashboard-intro' => 1, 'dashboard-intro-date' => 1,
  'dashboard-stats' => 1, 'dashboard-stat' => 4, 'dashboard-stat-label' => 4, 'stat' => 4,
  'dashboard-panels' => 1, 'dashboard-panel' => 2, 'revenue-chart' => 1, 'revenue-day' => 7,
  'parking-area-list' => 1, 'parking-area-header' => 1, 'parking-progress' => 1,
  'dashboard-table-card' => 1, 'table-wrap' => 1, 'dashboard-status' => 1,
];
$bad = 0;
foreach ($required as $cls => $min) {
    $n = preg_match_all('/class="[^"]*\b'.preg_quote($cls,'/').'\b[^"]*"/', $html);
    $ok = $n >= $min;
    if (!$ok) $bad++;
    printf("  %-24s expect>=%-3d got=%-3d %s" . PHP_EOL, $cls, $min, $n, $ok ? 'OK' : '<<< MISSING');
}
echo PHP_EOL . ($bad === 0 ? 'STRUKTUR LENGKAP' : "MISSING: $bad") . PHP_EOL;