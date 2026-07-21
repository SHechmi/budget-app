<?php
require_once __DIR__ . '/../config/database.php';

// Calcul solde utilisateur
function calculerSolde(int $userId): float {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN type='revenu' THEN montant ELSE 0 END), 0) AS revenus,
            COALESCE(SUM(CASE WHEN type='depense' THEN montant ELSE 0 END), 0) AS depenses
        FROM transactions WHERE id_utilisateur = ?
    ");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return (float)($row['revenus'] - $row['depenses']);
}

// Calcul total dépenses du mois
function totalDepensesMois(int $userId): float {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(montant), 0) AS total
        FROM transactions
        WHERE id_utilisateur = ? AND type = 'depense'
        AND MONTH(date) = MONTH(CURDATE())
        AND YEAR(date) = YEAR(CURDATE())
    ");
    $stmt->execute([$userId]);
    return (float)$stmt->fetchColumn();
}

// Calcul total revenus du mois
function totalRevenusMois(int $userId): float {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(montant), 0) AS total
        FROM transactions
        WHERE id_utilisateur = ? AND type = 'revenu'
        AND MONTH(date) = MONTH(CURDATE())
        AND YEAR(date) = YEAR(CURDATE())
    ");
    $stmt->execute([$userId]);
    return (float)$stmt->fetchColumn();
}

// Calcul pourcentage budget consommé
function pourcentageBudgetConsomme(int $userId): float {
    $db = getDB();
    // Récupère le budget actif
    $stmt = $db->prepare("
        SELECT plafond_global FROM budgets
        WHERE id_createur = ?
        AND date_debut <= CURDATE() AND date_fin >= CURDATE()
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $budget = $stmt->fetchColumn();
    if (!$budget || $budget == 0) return 0;
    $depenses = totalDepensesMois($userId);
    return round(($depenses / $budget) * 100, 1);
}

// Formatage montant
function formatMontant(float $montant): string {
    return number_format($montant, 2, ',', ' ') . ' TND';
}

// Formatage date
function formatDate(string $date): string {
    return date('d/m/Y', strtotime($date));
}

// Vérification session active
function isConnected(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}