<?php require_once APPROOT . '/views/includes/header.php'; ?>
<?php if(isset(
    $error)) : ?>
    <div class="alert alert-danger" style="max-width: 400px; margin: 1rem auto;">
        <?php echo $error; ?>
    </div>
<?php endif; ?>
<?php if(isset(
    $errors) && is_array($errors)) : ?>
    <div class="alert alert-danger" style="max-width: 400px; margin: 1rem auto;">
        <ul style="margin-bottom:0;">
            <?php foreach($errors as $fieldErrors) {
                foreach($fieldErrors as $err) {
                    echo '<li>' . $err . '</li>';
                }
            } ?>
        </ul>
    </div>
<?php endif; ?>
<div class="row">
    <div class="card" style="max-width: 400px; margin: 2rem auto;">
        <h2 style="text-align: center; margin-bottom: 2rem;">Register</h2>
        <form action="/auth/register" method="POST">
            <div class="form-group">
                <label for="name" class="form-label">Nama Lengkap</label>
                <input type="text" id="name" name="name" class="form-control" required value="<?php echo isset($name) ? htmlspecialchars($name) : '' ?>">
            </div>
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control" required value="<?php echo isset($email) ? htmlspecialchars($email) : '' ?>">
            </div>
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Register</button>
        </form>
        <p style="text-align: center; margin-top: 1rem;">
            Sudah punya akun? <a href="/auth/login" style="color: var(--primary-color);">Login</a>
        </p>
    </div>
</div>
<?php require_once APPROOT . '/views/includes/footer.php'; ?> 