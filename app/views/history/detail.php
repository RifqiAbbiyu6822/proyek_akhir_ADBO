<?php require_once APPROOT . '/views/includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Rental Details</h3>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Lens Name:</div>
                    <div class="col-md-8"><?php echo $rental->lens_name; ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Description:</div>
                    <div class="col-md-8"><?php echo $rental->description; ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Rental Date:</div>
                    <div class="col-md-8"><?php echo date('d M Y', strtotime($rental->rental_date)); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Expected Return Date:</div>
                    <div class="col-md-8"><?php echo date('d M Y', strtotime($rental->return_date)); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Status:</div>
                    <div class="col-md-8">
                        <span class="badge bg-<?php echo $rental->status == 'active' ? 'primary' : 'success'; ?>">
                            <?php echo ucfirst($rental->status); ?>
                        </span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Price per Day:</div>
                    <div class="col-md-8">Rp <?php echo number_format($rental->price_per_day); ?></div>
                </div>
                <?php if($rental->status == 'returned'): ?>
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Fine Amount:</div>
                        <div class="col-md-8">
                            <?php 
                            $fine = $this->model('Fine')->getFinesByRental($rental->id);
                            if(!empty($fine)): 
                            ?>
                                Rp <?php echo number_format($fine[0]->amount); ?>
                                <span class="badge bg-<?php echo $fine[0]->status == 'paid' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($fine[0]->status); ?>
                                </span>
                            <?php else: ?>
                                No fine
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="mt-4">
                    <a href="<?php echo BASEURL; ?>/history" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to History
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once APPROOT . '/views/includes/footer.php'; ?> 