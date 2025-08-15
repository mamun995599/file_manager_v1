<?php
// Disable error output to prevent corrupting PDF stream
error_reporting(0);
ini_set('display_errors', 0);

// Base directory for files (adjust as needed)
$base_dir = realpath('C:/drives');

$file = $_GET['file'] ?? '';

// Resolve the absolute path of the requested file
$full_path = realpath($base_dir . DIRECTORY_SEPARATOR . $file);

// Validate file existence and extension
if (
    $full_path === false
    || !is_file($full_path)
    || strtolower(pathinfo($full_path, PATHINFO_EXTENSION)) !== 'pdf'
) {
    http_response_code(404);
    exit('File not found or access denied.');
}

// Restrict access to allowed base directories (security)
$allowed_dirs = [
    realpath('C:/drives'),
    realpath('D:/'),
    realpath('E:/'),
    realpath('I:/'),
];

$allowed = false;
foreach ($allowed_dirs as $allowed_dir) {
    if ($allowed_dir && strpos($full_path, $allowed_dir) === 0) {
        $allowed = true;
        break;
    }
}

if (!$allowed) {
    http_response_code(403);
    exit('Access forbidden.');
}

$size = filesize($full_path);
$start = 0;
$end = $size - 1;
$length = $size;

// Support HTTP Range requests for PDF.js
if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches)) {
    $start = intval($matches[1]);
    if ($matches[2] !== '') {
        $end = intval($matches[2]);
    }
    $length = $end - $start + 1;

    header('HTTP/1.1 206 Partial Content');
    header("Content-Range: bytes $start-$end/$size");
} else {
    header('HTTP/1.1 200 OK');
}

// Send headers
header('Content-Type: application/pdf');
header("Content-Length: $length");
header('Accept-Ranges: bytes');
header('Content-Disposition: inline; filename="' . basename($full_path) . '"');

// Output file content with support for partial content
$fp = fopen($full_path, 'rb');
if ($fp === false) {
    http_response_code(500);
    exit('Failed to open file.');
}
fseek($fp, $start);

$buffer_size = 8192;
while (!feof($fp) && ftell($fp) <= $end) {
    $read_length = min($buffer_size, $end - ftell($fp) + 1);
    echo fread($fp, $read_length);
    flush();
}

fclose($fp);
exit;
