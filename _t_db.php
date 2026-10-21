<?php
$p = new PDO('mysql:host=127.0.0.1;dbname=db_parkir;charset=utf8mb4','root','');
foreach ($p->query('SELECT id_parkir, status FROM tb_transaksi ORDER BY id_parkir DESC LIMIT 5') as $r) {
  echo $r['id_parkir'] . ' ' . $r['status'] . PHP_EOL;
}