<?php
/**
 * Vercel Serverless Router Entry Point for UniCRM
 * Handles multi-page routing on Vercel's serverless PHP runtime
 */

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$parsedPath = parse_url($requestUri, PHP_URL_PATH);
$path = ltrim($parsedPath, '/');

// Default to root index.php
if ($path === '' || $path === 'index.php') {
    require __DIR__ . '/../index.php';
    exit;
}

$rootDir = realpath(__DIR__ . '/..');
$targetFile = realpath(__DIR__ . '/../' . $path);

// Security validation: verify path resides within project directory
if ($targetFile && strpos($targetFile, $rootDir) === 0 && file_exists($targetFile)) {
    if (is_dir($targetFile)) {
        $indexPath = rtrim($targetFile, '/\\') . '/index.php';
        if (file_exists($indexPath)) {
            $_SERVER['SCRIPT_NAME'] = '/' . ltrim(substr($indexPath, strlen($rootDir)), '/\\');
            $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
            chdir(dirname($indexPath));
            require $indexPath;
            exit;
        }
    } else {
        $_SERVER['SCRIPT_NAME'] = '/' . ltrim(substr($targetFile, strlen($rootDir)), '/\\');
        $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
        chdir(dirname($targetFile));
        require $targetFile;
        exit;
    }
}

// 404 handler
http_response_code(404);
echo "<!DOCTYPE html><html><head><title>404 Not Found</title><style>body{font-family:sans-serif;padding:40px;text-align:center;}h1{color:#4338ca;}</style></head><body><h1>404 - Page Not Found</h1><p>The requested page <code>" . htmlspecialchars($parsedPath) . "</code> was not found on this server.</p><p><a href='/'>Return to Home</a></p></body></html>";
