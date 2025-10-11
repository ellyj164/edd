<?php
require_once __DIR__ . '/../includes/init.php';
$page_title = 'Start Selling';
includeHeader($page_title);
?>
<div class="container">
    <div class="card mt-4">
        <div class="card-body">
            <h1>Start Selling on FezaMarket</h1>
            <p>Begin your selling journey on FezaMarket and reach millions of buyers worldwide.</p>
            <p><a href="<?php echo getSellingUrl(); ?>" class="btn btn-primary">Get Started</a></p>
        </div>
    </div>
</div>
<?php includeFooter(); ?>
