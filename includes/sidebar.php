<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$currentPage = basename($_SERVER['PHP_SELF']);
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
?>
<aside class="col-md-2 sidebar py-4">
    <div class="text-center text-white mb-4">
        <h4 class="fw-bold"><i class="fas fa-chart-line me-2"></i>Budget App</h4>
        <p class="small text-secondary"><?= htmlspecialchars(trim($_SESSION['prenom'] ?? '') . ' ' . trim($_SESSION['nom'] ?? '')) ?></p>
        <?php if ($isAdmin): ?>
            <span class="badge bg-danger mt-2"><i class="fas fa-user-shield me-1"></i>Administrateur</span>
        <?php else: ?>
            <span class="badge bg-info mt-2"><i class="fas fa-user me-1"></i>Utilisateur</span>
        <?php endif; ?>
    </div>
    <nav class="nav flex-column px-2">
        
        <?php if ($isAdmin): ?>
            <!-- ========== MENU ADMINISTRATEUR ========== -->
            <a class="nav-link <?= $currentPage == 'dashboard.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>views/dashboard.php">
                <i class="fas fa-chart-line me-2"></i>Tableau de bord Admin
            </a>
            <a class="nav-link <?= $currentPage == 'admin.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>views/admin.php">
                <i class="fas fa-users me-2"></i>Gestion utilisateurs
            </a>
            <a class="nav-link <?= $currentPage == 'profile.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>views/profile.php">
                <i class="fas fa-user me-2"></i>Mon profil
            </a>
            
        <?php else: ?>
            <!-- ========== MENU UTILISATEUR STANDARD ========== -->
            <a class="nav-link <?= $currentPage == 'dashboard.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>views/dashboard.php">
                <i class="fas fa-chart-line me-2"></i>Tableau de bord
            </a>
            <a class="nav-link <?= $currentPage == 'transactions.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>views/transactions.php">
                <i class="fas fa-exchange-alt me-2"></i>Transactions
            </a>
            <a class="nav-link <?= $currentPage == 'budgets.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>views/budgets.php">
                <i class="fas fa-wallet me-2"></i>Budgets
            </a>
            <a class="nav-link <?= $currentPage == 'categories.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>views/categories.php">
                <i class="fas fa-list me-2"></i>Catégories
            </a>
            <a class="nav-link <?= $currentPage == 'alertes.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>views/alertes.php">
                <i class="fas fa-bell me-2"></i>Alertes
                <?php
                // Compter alertes non lues pour le badge
                require_once __DIR__ . '/../models/Alerte.php';
                $unreadCount = Alerte::countUnread($_SESSION['user_id']);
                if ($unreadCount > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-2"><?= $unreadCount ?></span>
                <?php endif; ?>
            </a>
            <a class="nav-link <?= $currentPage == 'budget_partage.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>views/budget_partage.php">
                <i class="fas fa-users me-2"></i>Budget partagé
            </a>
            <a class="nav-link <?= $currentPage == 'profile.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>views/profile.php">
                <i class="fas fa-user me-2"></i>Mon profil
            </a>
        <?php endif; ?>
        
        <!-- ========== MENU COMMUN ========== -->
        <hr class="bg-secondary my-3">
        <a class="nav-link text-danger" href="<?= BASE_URL ?>views/logout.php">
            <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
        </a>
    </nav>
</aside>