<?php
include "auth.php";
include "../config/koneksi.php";

$id_order = (int)($_GET['id_order'] ?? 0);
if ($id_order <= 0) {
    http_response_code(400);
    exit('Bukti pembayaran tidak valid.');
}

$stmt = mysqli_prepare($koneksi, "SELECT COALESCE(NULLIF(o.bukti_pembayaran,''), p.bukti_pembayaran) AS bukti FROM orders o LEFT JOIN pembayaran p ON p.id_order=o.id_order WHERE o.id_order=? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $id_order);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$raw = trim((string)($row['bukti'] ?? ''));
if ($raw === '') {
    http_response_code(404);
    exit('Bukti pembayaran tidak tersedia.');
}

$raw = str_replace('\\', '/', $raw);
$base = realpath(__DIR__ . '/../assets/uploads');
$candidates = [];
if ($base !== false) {
    $clean = ltrim($raw, '/');
    $candidates[] = $base . '/' . $clean;
    $candidates[] = $base . '/bukti_pembayaran/' . basename($clean);
    $candidates[] = $base . '/' . basename($clean);
}
$file = null;
foreach ($candidates as $candidate) {
    $real = realpath($candidate);
    if ($real && $base && str_starts_with($real, $base . DIRECTORY_SEPARATOR) && is_file($real)) {
        $file = $real;
        break;
    }
}

// Fallback: cari nama file di seluruh folder upload agar data lama tetap bisa dibuka.
if (!$file && $base && is_dir($base)) {
    $target = basename($raw);
    try {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile() && $f->getFilename() === $target) {
                $file = $f->getPathname();
                break;
            }
        }
    } catch (Throwable $e) {}
}

if (!$file || !is_file($file)) {
    http_response_code(404);
    exit('File bukti pembayaran tidak ditemukan di server.');
}

$mime = function_exists('mime_content_type') ? mime_content_type($file) : 'application/octet-stream';
$allowed = ['image/jpeg','image/png','image/webp','image/gif','application/pdf'];
if (!in_array($mime, $allowed, true)) $mime = 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($file));
header('Content-Disposition: inline; filename="' . basename($file) . '"');
readfile($file);
exit;
