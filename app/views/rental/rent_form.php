<?php require_once APPROOT . '/views/includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center">Rent a Lens</h3>
            </div>
            <div class="card-body">
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="<?php echo BASEURL; ?>/rental/rent" method="POST">
                    <div class="mb-3">
                        <label for="lens_id" class="form-label">Select Lens</label>
                        <select class="form-select" id="lens_id" name="lens_id" required>
                            <option value="">Choose a lens...</option>
                            <?php foreach($lenses as $lens): ?>
                                <option value="<?php echo $lens->id; ?>">
                                    <?php echo $lens->name; ?> - Rp <?php echo number_format($lens->price_per_day); ?>/day
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="return_date" class="form-label">Return Date</label>
                        <input type="date" class="form-control" id="return_date" name="return_date" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Rent Lens</button>
                        <a href="<?php echo BASEURL; ?>/return" class="btn btn-secondary">Return a Lens</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once APPROOT . '/views/includes/footer.php'; ?> 