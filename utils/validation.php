<?php
// Nettoie un input texte
function cleanInput(string $data): string {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Valide un email
function validEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Valide un montant positif
function validMontant(mixed $montant): bool {
    return is_numeric($montant) && (float)$montant > 0;
}

// Valide une date (format Y-m-d)
function validDate(string $date): bool {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// Valide mot de passe (min 6 caractères) - UNE SEULE FOIS
function validPassword(string $pwd): bool {
    return strlen($pwd) >= 6;
}

?>