<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function rupiah($angka): string
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

function wajib_login(): void
{
    if (empty($_SESSION['id_user'])) {
        header('Location: /aplikasi-parkir/auth/login.php');
        exit;
    }
}

function wajib_role(array $roles): void
{
    wajib_login();

    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        http_response_code(403);
        $role = $_SESSION['role'] ?? '';
        $dashboard = [
            'admin' => '/aplikasi-parkir/admin/dashboard.php',
            'petugas' => '/aplikasi-parkir/petugas/transaksi.php',
            'owner' => '/aplikasi-parkir/owner/dashboard.php',
        ];

        if (isset($dashboard[$role])) {
            exit(
                'Akses ditolak. Akun Anda memiliki role ' .
                e(ucfirst($role)) .
                '. <a href="' . e($dashboard[$role]) .
                '">Kembali ke halaman akun</a>.'
            );
        }

        exit(
            'Akses ditolak. Role akun tidak dikenali. ' .
            '<a href="/aplikasi-parkir/auth/logout.php">Login kembali</a>.'
        );
    }
}

function redirect_role(): void
{
    $role = $_SESSION['role'] ?? '';

    if ($role === 'admin') {
        header('Location: /aplikasi-parkir/admin/dashboard.php');
    } elseif ($role === 'petugas') {
        header('Location: /aplikasi-parkir/petugas/transaksi.php');
    } elseif ($role === 'owner') {
        header('Location: /aplikasi-parkir/owner/dashboard.php');
    } else {
        unset($_SESSION['id_user'], $_SESSION['nama_lengkap'], $_SESSION['username'], $_SESSION['role']);
        header('Location: /aplikasi-parkir/auth/login.php');
    }

    exit;
}

function catat_aktivitas(PDO $pdo, string $aktivitas): void
{
    if (empty($_SESSION['id_user'])) {
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO tb_log_aktivitas (id_user, aktivitas)
         VALUES (?, ?)'
    );

    $stmt->execute([
        $_SESSION['id_user'],
        $aktivitas
    ]);
}
