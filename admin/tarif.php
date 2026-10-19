<?php
require_once __DIR__ . '/../config/koneksi.php'; require_once __DIR__ . '/../includes/functions.php'; wajib_role(['admin']);
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(($_POST['action']??'')==='save'){
  $id=(int)($_POST['id_tarif']??0); $jenis=$_POST['jenis_kendaraan']??''; $harga=(float)($_POST['tarif_per_jam']??0);
  if(!in_array($jenis,['motor','mobil','lainnya'],true)||$harga<0) $msg='Data tidak valid.';
  elseif($id){$q=$pdo->prepare('UPDATE tb_tarif SET jenis_kendaraan=?,tarif_per_jam=? WHERE id_tarif=?');$q->execute([$jenis,$harga,$id]);$msg='Tarif diperbarui.';}
  else{$q=$pdo->prepare('INSERT INTO tb_tarif(jenis_kendaraan,tarif_per_jam) VALUES(?,?)');$q->execute([$jenis,$harga]);$msg='Tarif ditambahkan.';}
  catat_aktivitas($pdo,'Mengelola tarif parkir');
 }elseif(($_POST['action']??'')==='delete'){try{$q=$pdo->prepare('DELETE FROM tb_tarif WHERE id_tarif=?');$q->execute([(int)$_POST['id']]);$msg='Tarif dihapus.';}catch(PDOException $e){$msg='Tarif sudah digunakan transaksi dan tidak dapat dihapus.';}}
}
$edit=null;if(isset($_GET['edit'])){$q=$pdo->prepare('SELECT * FROM tb_tarif WHERE id_tarif=?');$q->execute([(int)$_GET['edit']]);$edit=$q->fetch();}
$rows=$pdo->query('SELECT * FROM tb_tarif ORDER BY id_tarif DESC LIMIT 100')->fetchAll();$pageTitle='Tarif Parkir';require __DIR__.'/../includes/header.php';
?>
<h1>Tarif Parkir</h1><?php if($msg):?><div class="alert"><?=e($msg)?></div><?php endif;?>
<div class="card crud-card"><h2><?= $edit?'Edit Tarif':'Tambah Tarif' ?></h2><form method="post"><input type="hidden" name="action" value="save"><input type="hidden" name="id_tarif" value="<?=e($edit['id_tarif']??0)?>"><div class="form-grid"><div><label>Jenis kendaraan</label><select name="jenis_kendaraan"><?php foreach(['motor','mobil','lainnya'] as $j):?><option <?=($edit['jenis_kendaraan']??'motor')===$j?'selected':''?>><?=$j?></option><?php endforeach;?></select></div><div><label>Tarif per jam (Rp)</label><input type="number" min="0" name="tarif_per_jam" required value="<?=e($edit['tarif_per_jam']??'')?>" ></div></div><div class="crud-form-actions"><button class="crud-button crud-primary crud-save" type="submit">Simpan</button> <a class="crud-button crud-neutral crud-cancel" href="tarif.php">Batal</a></div></form></div>
<div class="card crud-card table-wrap"><table><tr><th>Jenis</th><th>Tarif per jam</th><th>Aksi</th></tr><?php foreach($rows as $r):?><tr><td><?=e($r['jenis_kendaraan'])?></td><td><?=rupiah($r['tarif_per_jam'])?></td><td><div class="crud-actions"><a class="crud-button crud-neutral crud-edit" href="?edit=<?=$r['id_tarif']?>">Edit</a><form class="inline-form" method="post" onsubmit="return confirm('Hapus tarif?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$r['id_tarif']?>"><button type="submit" class="crud-button crud-danger crud-delete">Hapus</button></form></div></td></tr><?php endforeach;?></table></div>
<?php require __DIR__.'/../includes/footer.php'; ?>
