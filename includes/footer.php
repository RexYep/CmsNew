<!-- Footer Section -->
    <footer class="footer-section">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12 text-center">
                    <p class="mb-0">&copy; 2026 Complaint Management System. All rights reserved.</p>
                </div>
                
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="<?php echo SITE_URL; ?>assets/js/script.js"></script>
    <?php
// Include global auto-refresh only on authenticated pages
if (isset($_SESSION['user_id'])) {
    include __DIR__ . '/global_auto_refresh.php';
}
?>
</body>
</html>