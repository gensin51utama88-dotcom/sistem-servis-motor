<?php
/**
 * Utilitas umum untuk halaman dashboard pengguna.
 * Kompatibel dengan koneksi PDO PostgreSQL/Supabase di config/koneksi.php.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/koneksi.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!function_exists('esc')) {
    function esc($value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('pagehead')) {
    function pagehead(string $title): void
    {
        $safeTitle = esc($title);
        echo <<<HTML
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$safeTitle} | ServisKu</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f1f5f9;color:#172033;font:15px system-ui,Arial,sans-serif}
.wrap{max-width:1050px;margin:0 auto;padding:28px 18px}.top{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:22px}
.brand{font-size:22px;font-weight:800;color:#1d4ed8}.nav{display:flex;gap:10px;flex-wrap:wrap}.btn{display:inline-block;border:0;border-radius:10px;padding:10px 14px;background:#2563eb;color:#fff;text-decoration:none;font-weight:700}
.btn.secondary{background:#e2e8f0;color:#1e293b}.card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:22px;box-shadow:0 8px 24px #0f172a0a;margin-bottom:16px}
.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.muted{color:#64748b}.stat{font-size:30px;font-weight:800;margin-top:8px}.avatar{width:76px;height:76px;object-fit:cover;border-radius:50%;border:2px solid #dbeafe}
h1,h2{margin-top:0}@media(max-width:650px){.wrap{padding:18px 12px}.grid{grid-template-columns:1fr}.card{padding:18px}}
</style>
</head>
<body><main class="wrap"><header class="top"><div class="brand">🔧 ServisKu</div>
<nav class="nav"><a class="btn secondary" href="dashboard.php">Dashboard</a>
<a class="btn secondary" href="pengaturan_user.php">Pengaturan</a>
<a class="btn" href="logout.php">Keluar</a></nav></header>
HTML;
    }
}

if (!function_exists('pagefoot')) {
    function pagefoot(): void
    {
        echo '<footer class="muted" style="text-align:center;padding:14px 0">ServisKu · Sistem Manajemen Servis Motor</footer></main></body></html>';
    }
}
