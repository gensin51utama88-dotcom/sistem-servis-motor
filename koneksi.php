<?php
/** Koneksi PostgreSQL Supabase melalui PDO. Atur variabel lingkungan di hosting. */
$envFile = dirname(__DIR__) . '/.env';
if (is_file($envFile) && is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key); $value = trim(trim($value), "\"'");
        if ($key !== '' && getenv($key) === false) putenv($key . '=' . $value);
    }
}
$host = getenv('SUPABASE_DB_HOST') ?: '';
$port = getenv('SUPABASE_DB_PORT') ?: '5432';
$db   = getenv('SUPABASE_DB_NAME') ?: 'postgres';
$user = getenv('SUPABASE_DB_USER') ?: 'postgres';
$pass = getenv('SUPABASE_DB_PASSWORD') ?: '';
if ($host === '' || $pass === '') {
    http_response_code(500);
    exit('Konfigurasi database belum lengkap. Atur SUPABASE_DB_HOST dan SUPABASE_DB_PASSWORD.');
}
try {
    $dsn = "pgsql:host={$host};port={$port};dbname={$db};sslmode=require";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Koneksi database gagal. Periksa konfigurasi PostgreSQL/Supabase pada environment hosting.');
}
require_once __DIR__ . '/../includes/auth.php';
authStartSession();
rememberRestore($pdo);
