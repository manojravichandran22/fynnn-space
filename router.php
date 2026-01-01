<?php

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 🔴 STEP 1: Redirect .php → clean URL
if (str_ends_with($uri, '.php')) {
    $clean = str_replace('.php', '', $uri);
    header("Location: $clean", true, 301);
    exit;
}

// remove trailing slash
$uri = rtrim($uri, '/');

// home
if ($uri === '' || $uri === '/') {
    require 'index.php';
    exit;
}

// serve assets only (css/js/images)
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|webp)$/', $uri)) {
    return false;
}

// map clean URL to php file
$file = __DIR__ . $uri . '.php';

if (file_exists($file)) {
    require $file;
    exit;
}

// 404
http_response_code(404);
echo "404 Page Not Found";
