<?php require_once APPROOT . '/views/includes/header.php'; ?>

<div class="row justify-content-center fade-in">
    <div class="col-md-8">
        <div class="card" style="border: 2px solid var(--vintage-accent);">
            <div class="card-header" style="background: var(--vintage-accent); color: var(--vintage-light);">
                <h3 class="mb-0">Error</h3>
            </div>
            <div class="card-body">
                <h5 class="card-title" style="color: var(--vintage-accent);">
                    <?php echo isset($error['type']) ? $error['type'] : 'Error'; ?>
                </h5>
                <p class="card-text"><?php echo isset($error['message']) ? $error['message'] : 'Terjadi kesalahan.'; ?></p>
                <?php if(isset($error['trace']) && ini_get('display_errors')): ?>
                    <div class="mt-4">
                        <h6>Stack Trace:</h6>
                        <pre class="bg-light p-3" style="background: var(--vintage-light); color: var(--vintage-text); border-radius: 8px;"><code><?php echo $error['trace']; ?></code></pre>
                    </div>
                <?php endif; ?>
                <div class="mt-4">
                    <a href="<?php echo BASEURL; ?>" class="btn btn-primary">Back to Home</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once APPROOT . '/views/includes/footer.php'; ?> 