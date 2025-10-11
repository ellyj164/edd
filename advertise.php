<?php
require_once __DIR__ . '/includes/init.php';
$page_title = 'Advertise';
includeHeader($page_title);
?>

<div class="container">
    <div class="row justify-center">
        <div class="col-10">
            <div class="card mt-4">
                <div class="card-body">
                    <h1 class="card-title">Advertise</h1>
                    <p>Advertise with FezaMarket - Promote your business on our platform.</p>
                    <p>This page is currently being developed. Please check back soon for more information.</p>
                    <p>For immediate assistance, please <a href="/contact.php">contact us</a>.</p>
                    <p><strong>Last Updated:</strong> <?php echo date('F d, Y'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php includeFooter(); ?>