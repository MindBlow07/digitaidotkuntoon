    </main>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="bi bi-laptop"></i> DigiTaidot Kuntoon!</h5>
                    <p class="mb-0">Paranna digitaalisia taitojasi helposti ja kustannustehokkaasti.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h6>Yhteystiedot</h6>
                    <p class="mb-0">
                        <i class="bi bi-envelope"></i> arttuz311@gmail.com<br>
                        <i class="bi bi-calendar"></i> © <?php echo date('Y'); ?> DigiTaidot Kuntoon!
                    </p>
                </div>
            </div>
            <hr class="my-3">
            <div class="row">
                <div class="col-12 text-center">
                    <small>
                        <a href="#" class="text-light me-3">Tietosuoja</a>
                        <a href="#" class="text-light me-3">Käyttöehdot</a>
                        <a href="#" class="text-light">Tuki</a>
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="js/main.js"></script>
    
    <!-- PayPal nappulat -->
    <?php if (isset($page_title) && $page_title === 'Tilaa palvelu'): ?>
    <script>
        paypal.Buttons({
            style: {
                shape: 'rect',
                color: 'blue',
                layout: 'vertical',
                label: 'subscribe'
            },
            createSubscription: function(data, actions) {
                return actions.subscription.create({
                    'plan_id': '<?php echo PAYPAL_PLAN_ID; ?>'
                });
            },
            onApprove: function(data, actions) {
                // Siirry success-sivulle
                window.location.href = 'success.php?subscriptionID=' + data.subscriptionID;
            },
            onError: function(err) {
                console.error('PayPal-virhe:', err);
                alert('Maksussa tapahtui virhe. Yritä uudelleen.');
            }
        }).render('#paypal-button-container');
    </script>
    <?php endif; ?>

</body>
</html>
