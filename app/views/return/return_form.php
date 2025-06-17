<?php require_once APPROOT . '/views/includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center">Return a Lens</h3>
            </div>
            <div class="card-body">
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if(empty($rentals)): ?>
                    <div class="alert alert-info">You don't have any active rentals.</div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach($rentals as $rental): ?>
                            <div class="list-group-item">
                                <h5 class="mb-1"><?php echo $rental->lens_name; ?></h5>
                                <p class="mb-1">Rental Date: <?php echo date('d M Y', strtotime($rental->rental_date)); ?></p>
                                <p class="mb-1">Expected Return: <?php echo date('d M Y', strtotime($rental->return_date)); ?></p>
                                <form action="<?php echo BASEURL; ?>/return/process" method="POST" class="mt-2">
                                    <input type="hidden" name="rental_id" value="<?php echo $rental->id; ?>">
                                    <button type="submit" class="btn btn-primary">Return Lens</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="mt-3">
                    <a href="<?php echo BASEURL; ?>/rental" class="btn btn-secondary">Rent a Lens</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once APPROOT . '/views/includes/footer.php'; ?> 