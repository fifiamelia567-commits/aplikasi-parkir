<?php
require_once __DIR__ . '/../config/koneksi.php';require_once __DIR__ . '/../includes/functions.php';wajib_role(['admin']);
$rows=$pdo->query('SELECT l.id_log,l.aktivitas,l.waktu_aktivitas,u.nama_lengkap,u.username FROM tb_log_aktivitas l JOIN tb_user u ON u.id_user=l.id_user ORDER BY l.waktu_aktivitas DESC LIMIT 200')->fetchAll();$pageTitle='Log Aktivitas';require __DIR__.'/../includes/header.php';
?>
<h1>Log Aktivitas</h1><div class="card table-wrap"><table><tr><th>Waktu</th><th>User</th><th>Username</th><th>Aktivitas</th></tr><?php foreach($rows as $r):?><tr><td><?=e($r['waktu_aktivitas'])?></td><td><?=e($r['nama_lengkap'])?></td><td><?=e($r['username'])?></td><td><?=e($r['aktivitas'])?></td></tr><?php endforeach;?></table></div>
<?php require __DIR__.'/../includes/footer.php'; ?>
