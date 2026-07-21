<?php
require_once __DIR__ . '/../config/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
?>

<footer class="footer">
        <div class="container">
            <p>&copy; 2026 Budget App - Gestion collaborative de budget personnel</p>
            <p class="mt-2">
                <small>
                    <i class="fas fa-envelope me-1"></i> contact@budgetapp.com |
                    <i class="fas fa-phone me-1"></i> +216 27158743
                </small>
            </p>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>