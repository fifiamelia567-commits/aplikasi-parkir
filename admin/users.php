<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/functions.php';
wajib_role(['admin']);
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id_user'] ?? 0);
        $nama = trim($_POST['nama_lengkap'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'petugas';
        $status = isset($_POST['status_aktif']) ? 1 : 0;
        if ($nama === '' || $username === '' || !in_array($role, ['admin','petugas','owner'], true)) {
            $msg = 'Lengkapi data dengan benar.';
        } elseif ($id > 0) {
            if ($password !== '') {
                $q = $pdo->prepare('UPDATE tb_user SET nama_lengkap=?,username=?,password=?,role=?,status_aktif=? WHERE id_user=?');
                $q->execute([$nama,$username,password_hash($password, PASSWORD_DEFAULT),$role,$status,$id]);
            } else {
                $q = $pdo->prepare('UPDATE tb_user SET nama_lengkap=?,username=?,role=?,status_aktif=? WHERE id_user=?');
                $q->execute([$nama,$username,$role,$status,$id]);
            }
            catat_aktivitas($pdo, 'Mengubah data user');
            $msg = 'Data user diperbarui.';
        } else {
            if ($password === '') $msg = 'Password wajib diisi untuk user baru.';
            else {
                $q = $pdo->prepare('INSERT INTO tb_user(nama_lengkap,username,password,role,status_aktif) VALUES(?,?,?,?,?)');
                $q->execute([$nama,$username,password_hash($password, PASSWORD_DEFAULT),$role,$status]);
                catat_aktivitas($pdo, 'Menambah user');
                $msg = 'User berhasil ditambahkan.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id_user'] ?? 0);
        if ($id === (int)$_SESSION['id_user']) $msg = 'Akun yang sedang digunakan tidak dapat dihapus.';
        else {
            try {
                $q = $pdo->prepare('DELETE FROM tb_user WHERE id_user=?');
                $q->execute([$id]);
                catat_aktivitas($pdo, 'Menghapus user');
                $msg = 'User dihapus.';
            } catch (PDOException $e) { $msg = 'User tidak dapat dihapus karena masih digunakan pada data lain.'; }
        }
    }
}
$edit = null;
if (isset($_GET['edit'])) { $q=$pdo->prepare('SELECT id_user,nama_lengkap,username,role,status_aktif FROM tb_user WHERE id_user=?'); $q->execute([(int)$_GET['edit']]); $edit=$q->fetch(); }
$rows = $pdo->query('SELECT id_user,nama_lengkap,username,role,status_aktif FROM tb_user ORDER BY id_user DESC LIMIT 200')->fetchAll();
$pageTitle='Data User'; require __DIR__ . '/../includes/header.php';
?>
<h1>Kelola User</h1><?php if($msg): ?><div class="alert"><?= e($msg) ?></div><?php endif; ?>
<div class="card crud-card"><h2><?= $edit?'Edit User':'Tambah User' ?></h2>
<form method="post"><input type="hidden" name="action" value="save"><input type="hidden" name="id_user" value="<?= e($edit['id_user']??0) ?>">
<div class="form-grid">
<div><label>Nama lengkap</label><input name="nama_lengkap" required value="<?= e($edit['nama_lengkap']??'') ?>"></div>
<div><label>Username</label><input name="username" required value="<?= e($edit['username']??'') ?>"></div>
<div><label>Password <?= $edit?'(kosongkan jika tidak diganti)':'' ?></label><input type="password" name="password" <?= $edit?'':'required' ?>></div>
<div><label>Role</label><select name="role"><?php foreach(['admin','petugas','owner'] as $r): ?><option value="<?= $r ?>" <?= (($edit['role']??'petugas')===$r)?'selected':'' ?>><?= ucfirst($r) ?></option><?php endforeach; ?></select></div>
</div><label class="crud-status"><input type="checkbox" name="status_aktif" <?= !isset($edit['status_aktif']) || $edit['status_aktif'] ? 'checked':'' ?>> Akun aktif</label><div class="crud-form-actions"><button class="crud-button crud-primary crud-save" type="submit">Simpan</button> <a class="crud-button crud-neutral crud-cancel" href="users.php">Batal</a></div></form></div>
<div class="card crud-card"><h2>Daftar User</h2><div class="table-wrap"><table><tr><th>Nama</th><th>Username</th><th>Role</th><th>Status</th><th>Aksi</th></tr>
<?php foreach($rows as $r): ?><tr><td><?= e($r['nama_lengkap']) ?></td><td><?= e($r['username']) ?></td><td><?= e($r['role']) ?></td><td><?= $r['status_aktif']?'Aktif':'Nonaktif' ?></td><td><div class="crud-actions"><a class="crud-button crud-neutral crud-edit" href="?edit=<?= $r['id_user'] ?>">Edit</a><form method="post" class="inline-form" onsubmit="return confirm('Hapus user ini?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id_user" value="<?= $r['id_user'] ?>"><button type="submit" class="crud-button crud-danger crud-delete">Hapus</button></form></div></td></tr><?php endforeach; ?>
</table></div></div><?php require __DIR__ . '/../includes/footer.php'; ?>
