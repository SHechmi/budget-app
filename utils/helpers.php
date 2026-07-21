<?php
// Redirection propre
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

// Génère un token CSRF
function generateCsrf(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Vérifie le token CSRF
function verifyCsrf(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
function format_currency($amount)
{
    return number_format((float)$amount, 2, '.', ' ');
}

function format_date_long($date)
{
    if (empty($date)) return '';
    $d = new DateTime($date);
    return $d->format('d/m/Y');
}
