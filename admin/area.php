<?php
require_once __DIR__ . '/../config/koneksi.php';require_once __DIR__ . '/../includes/functions.php';wajib_role(['admin']);$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(($_POST['action']??'')==='save'){$id=(int)($_POST['id_area']??0);$nama=trim($_POST['nama_area']??'');$kap=(int)($_POST['kapasitas']??0);$ter=(int)($_POST['terisi']??0);
 if($nama===''||$kap<1||$ter<0||$ter>$kap)$msg='Pastikan nama dan kapasitas benar serta terisi tidak melebihi kapasitas.';
 elseif($id){$q=$pdo->prepare('UPDATE tb_area_parkir SET nama_area=?,kapasitas=?,terisi=? WHERE id_area=?');$q->execute([$nama,$kap,$ter,$id]);$msg='Area diperbarui.';}
 else{$q=$pdo->prepare('INSERT INTO tb_area_parkir(nama_area,kapasitas,terisi) VALUES(?,?,?)');$q->execute([$nama,$kap,$ter]);$msg='Area ditambahkan.';}catat_aktivitas($pdo,'Mengelola area parkir');
 }elseif(($_POST['action']??'')==='delete'){try{$q=$pdo->prepare('DELETE FROM tb_area_parkir WHERE id_area=?');$q->execute([(int)$_POST['id']]);$msg='Area dihapus.';}catch(PDOException $e){$msg='Area sudah dipakai transaksi.';}}
}
$edit=null;if(isset($_GET['edit'])){$q=$pdo->prepare('SELECT * FROM tb_area_parkir WHERE id_area=?');$q->execute([(int)$_GET['edit']]);$edit=$q->fetch();}
$rows=$pdo->query('SELECT * FROM tb_area_parkir ORDER BY id_area DESC LIMIT 100')->fetchAll();$pageTitle='Area Parkir';require __DIR__.'/../includes/header.php';
?>
<h1>Area Parkir</h1><?php if($msg):?><div class="alert"><?=e($msg)?></div><?php endif;?>
<div class="card crud-card"><h2><?= $edit?'Edit Area':'Tambah Area' ?></h2><form method="post"><input type="hidden" name="action" value="save"><input type="hidden" name="id_area" value="<?=e($edit['id_area']??0)?>"><div class="form-grid"><div><label>Nama area</label><input name="nama_area" required value="<?=e($edit['nama_area']??'')?>"></div><div><label>Kapasitas</label><input type="number" min="1" name="kapasitas" required value="<?=e($edit['kapasitas']??'')?>"></div><div><label>Terisi</label><input type="number" min="0" name="terisi" required value="<?=e($edit['terisi']??0)?>"></div></div><div class="crud-form-actions"><button class="crud-button crud-primary crud-save" type="submit">Simpan</button> <a class="crud-button crud-neutral crud-cancel" href="area.php">Batal</a></div></form></div>
<div class="card crud-card table-wrap"><table><tr><th>Nama area</th><th>Kapasitas</th><th>Terisi</th><th>Sisa</th><th>Aksi</th></tr><?php foreach($rows as $r):?><tr><td><?=e($r['nama_area'])?></td><td><?=$r['kapasitas']?></td><td><?=$r['terisi']?></td><td><?=$r['kapasitas']-$r['terisi']?></td><td><div class="crud-actions"><a class="crud-button crud-neutral crud-edit" href="?edit=<?=$r['id_area']?>">Edit</a><form class="inline-form" method="post" onsubmit="return confirm('Hapus area?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$r['id_area']?>"><button type="submit" class="crud-button crud-danger crud-delete">Hapus</button></form></div></td></tr><?php endforeach;?></table></div>
<?php require __DIR__.'/../includes/footer.php'; ?>
