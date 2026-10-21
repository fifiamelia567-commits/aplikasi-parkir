<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/functions.php';
wajib_role(['admin']);

$urutanTabel = [
    'tb_user',
    'tb_area_parkir',
    'tb_tarif',
    'tb_kendaraan',
    'tb_transaksi',
    'tb_log_aktivitas',
];
$urutanHapus = array_reverse($urutanTabel);
$databaseName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
$msg = '';
$tipeMsg = 'success';

if (empty($_SESSION['backup_csrf'])) {
    $_SESSION['backup_csrf'] = bin2hex(random_bytes(32));
}

if (($_GET['download'] ?? '') === '1') {
    $lines = [
        '-- ParkirKu SQL Backup v1',
        '-- Database: ' . $databaseName,
        '-- Generated: ' . date(DATE_ATOM),
        'SET FOREIGN_KEY_CHECKS = 0;',
    ];

    foreach ($urutanHapus as $table) {
        $lines[] = "DELETE FROM `{$table}`;";
    }

    foreach ($urutanTabel as $table) {
        $columns = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll();
        $columnNames = array_column($columns, 'Field');
        $quotedColumns = implode(
            ', ',
            array_map(static fn ($column) => '`' . $column . '`', $columnNames)
        );
        $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $values = [];
            foreach ($columns as $column) {
                $value = $row[$column['Field']];
                if ($value === null) {
                    $values[] = 'NULL';
                } elseif (preg_match('/^(tinyint|smallint|mediumint|int|bigint|decimal|numeric|float|double|real|bit)/i', $column['Type'])) {
                    $numericValue = (string) $value;
                    if (!preg_match('/^-?\d+(?:\.\d+)?$/', $numericValue)) {
                        throw new RuntimeException('Nilai numerik tabel ' . $table . ' tidak valid.');
                    }
                    $values[] = $numericValue;
                } else {
                    $stringValue = (string) $value;
                    $values[] = $stringValue === ''
                        ? "''"
                        : 'CONVERT(0x' . bin2hex($stringValue) . ' USING utf8mb4)';
                }
            }

            $lines[] = "INSERT INTO `{$table}` ({$quotedColumns}) VALUES (" .
                implode(', ', $values) . ');';
        }
    }

    $lines[] = 'SET FOREIGN_KEY_CHECKS = 1;';
    $sql = implode("\n", $lines) . "\n";
    $filename = 'parkirku-backup-' . date('Ymd-His') . '.sql';

    header('Content-Type: application/sql; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($sql));
    header('X-Content-Type-Options: nosniff');
    echo $sql;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['backup_csrf'], $csrf)) {
        http_response_code(400);
        $msg = 'Sesi formulir tidak valid. Muat ulang halaman dan coba kembali.';
        $tipeMsg = 'error';
    } elseif (($_POST['action'] ?? '') !== 'restore') {
        http_response_code(400);
        $msg = 'Permintaan tidak valid.';
        $tipeMsg = 'error';
    } elseif (($_POST['confirmation'] ?? '') !== 'PULIHKAN') {
        $msg = 'Ketik PULIHKAN sebagai konfirmasi sebelum melanjutkan.';
        $tipeMsg = 'error';
    } elseif (
        !isset($_FILES['backup_file']) ||
        $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK ||
        !is_uploaded_file($_FILES['backup_file']['tmp_name'])
    ) {
        $msg = 'Pilih file backup SQL yang valid untuk dipulihkan.';
        $tipeMsg = 'error';
    } else {
        $sql = file_get_contents($_FILES['backup_file']['tmp_name']);

        try {
            if ($sql === false) {
                throw new RuntimeException('File backup tidak dapat dibaca.');
            }

            $lines = preg_split('/\R/', $sql);
            if (
                !is_array($lines) ||
                ($lines[0] ?? '') !== '-- ParkirKu SQL Backup v1' ||
                ($lines[1] ?? '') !== '-- Database: ' . $databaseName ||
                !isset($lines[2]) ||
                !preg_match('/^-- Generated: .+$/', $lines[2])
            ) {
                throw new RuntimeException('File bukan backup SQL ParkirKu yang cocok dengan database ini.');
            }

            $commands = [];
            foreach (array_slice($lines, 3) as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                if (str_starts_with($line, '--')) {
                    throw new RuntimeException('File backup berisi baris SQL yang tidak dikenal.');
                }
                $commands[] = $line;
            }

            $expectedPrefix = ['SET FOREIGN_KEY_CHECKS = 0;'];
            foreach ($urutanHapus as $table) {
                $expectedPrefix[] = "DELETE FROM `{$table}`;";
            }

            if (array_slice($commands, 0, count($expectedPrefix)) !== $expectedPrefix) {
                throw new RuntimeException('Urutan perintah backup SQL tidak valid.');
            }

            $columnDefinitions = [];
            foreach ($urutanTabel as $table) {
                $columnDefinitions[$table] = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll();
            }

            $validatedRows = array_fill_keys($urutanTabel, []);
            $insertCommands = array_slice($commands, count($expectedPrefix));
            if (!$insertCommands || array_pop($insertCommands) !== 'SET FOREIGN_KEY_CHECKS = 1;') {
                throw new RuntimeException('Backup SQL tidak memiliki penanda akhir yang valid.');
            }

            foreach ($insertCommands as $command) {
                if (!preg_match(
                    '/^INSERT INTO `([a-z_]+)` \((.+)\) VALUES \((.*)\);$/',
                    $command,
                    $matches
                )) {
                    throw new RuntimeException('Backup SQL berisi perintah yang tidak diizinkan.');
                }

                $table = $matches[1];
                if (!array_key_exists($table, $columnDefinitions)) {
                    throw new RuntimeException('Backup SQL berisi tabel yang tidak dikenal.');
                }

                preg_match_all('/`([a-zA-Z0-9_]+)`/', $matches[2], $columnMatches);
                $columns = array_column($columnDefinitions[$table], 'Field');
                if ($columnMatches[1] !== $columns) {
                    throw new RuntimeException('Kolom backup SQL tidak cocok dengan tabel ' . $table . '.');
                }

                $tokens = explode(', ', $matches[3]);
                if (count($tokens) !== count($columns)) {
                    throw new RuntimeException('Jumlah nilai backup SQL tidak cocok dengan tabel ' . $table . '.');
                }

                $values = [];
                foreach ($tokens as $index => $token) {
                    if ($token === 'NULL') {
                        $values[] = null;
                    } elseif (preg_match('/^CONVERT\(0x([a-f0-9]+) USING utf8mb4\)$/i', $token, $hexMatch)) {
                        $decoded = hex2bin($hexMatch[1]);
                        if ($decoded === false) {
                            throw new RuntimeException('Nilai teks backup SQL tidak valid.');
                        }
                        $values[] = $decoded;
                    } elseif ($token === "''") {
                        $values[] = '';
                    } elseif (preg_match('/^-?\d+(?:\.\d+)?$/', $token)) {
                        $values[] = $token;
                    } else {
                        throw new RuntimeException('Nilai backup SQL berisi sintaks yang tidak diizinkan.');
                    }
                }

                $validatedRows[$table][] = $values;
            }

            $pdo->beginTransaction();
            foreach ($urutanHapus as $table) {
                $pdo->exec("DELETE FROM `{$table}`");
            }

            foreach ($urutanTabel as $table) {
                $columns = array_column($columnDefinitions[$table], 'Field');
                $quotedColumns = implode(
                    ', ',
                    array_map(static fn ($column) => '`' . $column . '`', $columns)
                );
                $placeholders = implode(', ', array_fill(0, count($columns), '?'));
                $insert = $pdo->prepare(
                    "INSERT INTO `{$table}` ({$quotedColumns}) VALUES ({$placeholders})"
                );

                foreach ($validatedRows[$table] as $values) {
                    $insert->execute($values);
                }
            }

            $pdo->commit();
            $msg = 'Database berhasil dipulihkan dari file backup.';
            try {
                catat_aktivitas($pdo, 'Memulihkan database dari backup');
            } catch (Throwable $logError) {
                error_log('Log pemulihan backup gagal disimpan: ' . $logError->getMessage());
                $msg .= ' Namun, log aktivitas pemulihan gagal disimpan.';
                $tipeMsg = 'error';
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Pemulihan backup database gagal: ' . $e->getMessage());
            $msg = 'Pemulihan gagal: ' . $e->getMessage();
            $tipeMsg = 'error';
        }
    }
}

$pageTitle = 'Backup & Restore';
require __DIR__ . '/../includes/header.php';
?>
<h1>Backup &amp; Restore Database</h1>

<?php if ($msg !== ''): ?>
    <div class="alert <?= $tipeMsg === 'error' ? 'error' : '' ?>" role="status">
        <?= e($msg) ?>
    </div>
<?php endif; ?>

<div class="backup-grid">
    <section class="card backup-card">
        <div class="backup-card-icon" aria-hidden="true">↓</div>
        <div class="backup-eyebrow">SALINAN DATA</div>
        <h2>Unduh backup</h2>
        <p>
            Simpan data seluruh tabel ParkirKu sebagai file SQL yang bisa diimpor
            melalui phpMyAdmin atau dipakai pada fitur pemulihan.
        </p>
        <a class="btn backup-download" href="backup.php?download=1">Unduh Backup SQL</a>
    </section>

    <section class="card backup-card backup-restore-card">
        <div class="backup-card-icon" aria-hidden="true">↑</div>
        <div class="backup-eyebrow">PEMULIHAN DATA</div>
        <h2>Pulihkan dari backup</h2>
        <p>
            Pemulihan akan mengganti seluruh isi enam tabel aplikasi. Gunakan file
            backup SQL yang dibuat oleh ParkirKu dan pastikan salinan terbaru sudah disimpan.
        </p>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['backup_csrf']) ?>">
            <input type="hidden" name="action" value="restore">

            <div class="field">
                <label for="backup_file">File backup SQL</label>
                <input
                    type="file"
                    id="backup_file"
                    name="backup_file"
                    accept=".sql,application/sql,text/plain"
                    required
                >
            </div>

            <div class="field">
                <label for="confirmation">Ketik PULIHKAN untuk mengonfirmasi</label>
                <input
                    type="text"
                    id="confirmation"
                    name="confirmation"
                    autocomplete="off"
                    required
                >
            </div>

            <button class="backup-restore-button" type="submit">
                Pulihkan Database
            </button>
        </form>
    </section>
</div>

<aside class="backup-notice">
    <strong>Penting sebelum memulihkan</strong>
    <ul>
        <li>Data kendaraan, transaksi, user, tarif, area, dan log saat ini akan diganti.</li>
        <li>File SQL hanya diterima jika format, database, tabel, dan kolomnya cocok.</li>
        <li>Jangan bagikan file backup karena berisi data aplikasi termasuk hash password.</li>
    </ul>
</aside>

<?php require __DIR__ . '/../includes/footer.php'; ?>
