<?php
$targets = [
 '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9'=>'admin1',
 '2dad904f71aa0dcf6ea1addaa084a5865ffe448e4d3f900668e1cc7e7b6153d7'=>'petugas1',
 '43a0d17178a9d26c9e0fe9a74b0b45e38d32f27aed887a008a54bf6e033bf7b9'=>'owner1',
];
$cands = ['123456','password','admin','admin123','petugas','petugas123','owner','owner123','1234','12345','secret','parkirku','admin1','petugas1','owner1'];
foreach ($cands as $c) {
  $h = hash('sha256', $c);
  foreach ($targets as $t => $u) {
    if ($h === $t) { $out[] = "$u => $c"; }
  }
}
file_put_contents(__DIR__.'/_pw.txt', implode(PHP_EOL, $out) ?: 'NO MATCH', LOCK_EX);