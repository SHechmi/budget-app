<?php
// ini_set AVANT session_start
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

define('APP_NAME', 'BudgetApp');

// Auto-detect BASE_URL robustly by locating the project root '/budget_app' in the script path
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/';
$projectSegment = '/budget_app';
$pos = strpos($scriptName, $projectSegment);
if ($pos !== false) {
    $basePath = substr($scriptName, 0, $pos + strlen($projectSegment)) . '/';
} else {
    // Fallback: use dirname of script and ensure trailing slash
    $basePath = rtrim(dirname($scriptName), '\\/') . '/';
}
$baseUrl = $protocol . $host . $basePath;
define('BASE_URL', $baseUrl);

define('UPLOAD_DIR', __DIR__ . '/../assets/img/uploads/');
define('SESSION_DURATION', 3600);