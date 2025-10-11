<?php if (!empty($sectionData['products'])): ?>
<div class="container my-5">
    <h2 class="text-center mb-4"><?php echo htmlspecialchars($sectionTitle); ?></h2>
    <div class="row">
        <?php foreach ($sectionData['products'] as $product): ?>
            <div class="col-md-3 col-sm-6 mb-4">
                <?php include SITE_ROOT . '/templates/partials/_product-card.php'; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>