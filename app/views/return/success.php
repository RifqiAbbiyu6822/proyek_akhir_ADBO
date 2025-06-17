<?php require_once APPROOT . '/views/includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body text-center">
                <h3 class="card-title text-success mb-4">Return Successful!</h3>
                <p class="card-text">Your lens has been successfully returned.</p>
                <?php if(isset($fine_amount) && $fine_amount > 0): ?>
                    <div class="alert alert-warning">
                        <p class="mb-0">Late return fine: Rp <?php echo number_format($fine_amount); ?></p>
                    </div>
                <?php endif; ?>
                <div class="mt-4">
                    <a href="<?php echo BASEURL; ?>/rental" class="btn btn-primary">Rent Another Lens</a>
                    <a href="<?php echo BASEURL; ?>/return" class="btn btn-secondary">Return Another Lens</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once APPROOT . '/views/includes/footer.php'; ?> 