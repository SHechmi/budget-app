<?php
// index.php - Page d'accueil publique
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$isLoggedIn = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? '';
$pageTitle = "Budget App - Gérez vos finances simplement";

// === RÉCUPÉRATION DES STATISTIQUES DYNAMIQUES ===
$db = getDB();

// 1. Nombre total d'utilisateurs actifs (comptes validés)
$stmt = $db->query("SELECT COUNT(*) FROM utilisateurs");
$totalUsers = $stmt->fetchColumn();

// 2. Nombre total de transactions
$stmt = $db->query("SELECT COUNT(*) FROM transactions");
$totalTransactions = $stmt->fetchColumn();

// 3. Nombre total de budgets
$stmt = $db->query("SELECT COUNT(*) FROM budgets");
$totalBudgets = $stmt->fetchColumn();

// 4. Nombre total d'alertes générées
$stmt = $db->query("SELECT COUNT(*) FROM alertes");
$totalAlertes = $stmt->fetchColumn();

// 5. Montant total des transactions (revenus + dépenses)
$stmt = $db->query("SELECT SUM(montant) FROM transactions");
$totalMontant = $stmt->fetchColumn();
if ($totalMontant === null) $totalMontant = 0;

// 6. Nombre d'utilisateurs inscrits ce mois-ci
$stmt = $db->query("SELECT COUNT(*) FROM utilisateurs WHERE MONTH(date_inscription) = MONTH(CURDATE()) AND YEAR(date_inscription) = YEAR(CURDATE())");
$newUsersThisMonth = $stmt->fetchColumn();

// 7. Taux de croissance (pourcentage)
$stmt = $db->query("SELECT COUNT(*) FROM utilisateurs WHERE MONTH(date_inscription) = MONTH(CURDATE() - INTERVAL 1 MONTH)");
$lastMonthUsers = $stmt->fetchColumn();
$growthRate = ($lastMonthUsers > 0) ? round(($newUsersThisMonth - $lastMonthUsers) / $lastMonthUsers * 100) : 100;

// Inclure le header
require_once __DIR__ . '/includes/header.php';
?>

    <!-- Navigation -->


    <!-- Hero Section -->
    <section class="hero">
        <div class="container text-center">
            <h1>
                <i class="fas fa-coins"></i> Gérez vos finances<br>
                <span style="color: #0d9488;">simplement et efficacement</span>
            </h1>
            <p>Budget App vous aide à suivre vos revenus, dépenses et budgets,<br>que vous soyez seul ou en équipe.</p>
            <?php if ($isLoggedIn): ?>
            <div class="hero-buttons">
                <a href="views/dashboard.php" class="btn btn-hero-primary">
                    <i class="fas fa-tachometer-alt me-2"></i>Accéder à mon tableau de bord
                </a>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <div class="section-title">
                <h2>Fonctionnalités principales</h2>
                <p>Tout ce dont vous avez besoin pour gérer votre budget</p>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Tableau de bord</h3>
                        <p>Visualisez vos revenus, dépenses et solde en temps réel avec des graphiques interactifs.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3>Alertes intelligentes</h3>
                        <p>Recevez des notifications quand vous approchez ou dépassez votre budget.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Gestion collaborative</h3>
                        <p>Partagez vos budgets avec votre famille, colocataires ou équipe.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <h3>Budgets personnalisés</h3>
                        <p>Créez des budgets par catégorie ou par période (mensuel, hebdomadaire).</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <h3>Statistiques détaillées</h3>
                        <p>Analysez vos dépenses par catégorie avec des graphiques camembert et courbes.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3>Sécurisé</h3>
                        <p>Vos données sont protégées avec authentification et chiffrement des mots de passe.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section - DYNAMIQUE -->
    <section id="stats" class="stats">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="stat-number"><?= number_format($totalUsers) ?>+</div>
                    <div class="stat-label">Utilisateurs actifs</div>
                    <small class="text-muted"><?= $newUsersThisMonth ?> nouveaux ce mois</small>
                </div>
                <div class="col-md-3">
                    <div class="stat-number"><?= number_format($totalTransactions) ?>+</div>
                    <div class="stat-label">Transactions traitées</div>
                    <small class="text-muted"><?= number_format($totalMontant, 0) ?> € cumulés</small>
                </div>
                <div class="col-md-3">
                    <div class="stat-number"><?= number_format($totalBudgets) ?>+</div>
                    <div class="stat-label">Budgets créés</div>
                    <small class="text-muted">Budgets individuels et partagés</small>
                </div>
                <div class="col-md-3">
                    <div class="stat-number"><?= number_format($totalAlertes) ?>+</div>
                    <div class="stat-label">Alertes générées</div>
                    <small class="text-muted">Pour vous tenir informé</small>
                </div>
            </div>
            
            <!-- Taux de croissance (optionnel) -->
            <div class="row mt-4">
                <div class="col-12 text-center">
                    <div class="growth-badge">
                        <i class="fas fa-chart-line"></i>
                        Croissance : 
                        <?php if ($growthRate >= 0): ?>
                            <span class="text-success">+<?= $growthRate ?>%</span>
                        <?php else: ?>
                            <span class="text-danger"><?= $growthRate ?>%</span>
                        <?php endif; ?>
                        d'utilisateurs ce mois
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section id="cta" class="cta">
        <div class="container">
            <h2>Prêt à maîtriser vos finances ?</h2>
            <p>Rejoignez des milliers d'utilisateurs qui gèrent déjà leur budget avec Budget App</p>
            <?php if (!$isLoggedIn): ?>
            <a href="views/register.php" class="btn btn-cta">
                <i class="fas fa-user-plus me-2"></i>C'est parti, c'est gratuit !
            </a>
            <?php else: ?>
            <a href="views/dashboard.php" class="btn btn-cta">
                <i class="fas fa-tachometer-alt me-2"></i>Aller à mon tableau de bord
            </a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <?php require_once __DIR__ . '/includes/footer.php'; ?>