<?php require_once APPROOT . '/views/includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body text-center">
                <h3 class="card-title text-success mb-4">Rental Successful!</h3>
                <p class="card-text">Your lens has been successfully rented.</p>
                <div class="mt-4">
                    <a href="<?php echo BASEURL; ?>/rental" class="btn btn-primary">Rent Another Lens</a>
                    <a href="<?php echo BASEURL; ?>/return" class="btn btn-secondary">Return a Lens</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once APPROOT . '/views/includes/footer.php'; ?> 