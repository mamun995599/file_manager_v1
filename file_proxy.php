<?php
// Base folder where symlinks (e.g., D:, E:) are stored
$base_dir = realpath('C:/drives');

// Get the requested file
$file = $_GET['file'] ?? '';
$full_path = realpath($base_dir . DIRECTORY_SEPARATOR . $file);

// Check if file exists
if ($full_path === false || !is_file($full_path)) {
    http_response_code(404);
    exit('❌ File not found or access denied.');
}

// ✅ Optional: Restrict access only to allowed drives (safety)
$allowed_dirs = [
    realpath('C:/drives'),
    realpath('D:/'),
    realpath('E:/'),
    // Add more if needed
	realpath('I:/'),
    // Add more if needed
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
    exit('🚫 Access to this location is forbidden.');
}

// Detect MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $full_path);
finfo_close($finfo);

// Handle byte range for streaming
$size = filesize($full_path);
$start = 0;
$end = $size - 1;
$length = $size;

if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches)) {
    $start = (int)$matches[1];
    if ($matches[2] !== '') {
        $end = (int)$matches[2];
    }
    $length = $end - $start + 1;
    header('HTTP/1.1 206 Partial Content');
    header("Content-Range: bytes $start-$end/$size");
} else {
    header('HTTP/1.1 200 OK');
}

// Send headers
header("Content-Type: $mime");
header("Content-Length: $length");
header("Accept-Ranges: bytes");
header('Content-Disposition: inline; filename="' . basename($full_path) . '"');

// Stream the file
$fp = fopen($full_path, 'rb');
fseek($fp, $start);

while (!feof($fp) && ftell($fp) <= $end) {
    echo fread($fp, 8192);
    flush();
}

fclose($fp);
exit;
