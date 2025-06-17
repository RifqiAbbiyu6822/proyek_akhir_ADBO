<?php require_once APPROOT . '/views/includes/header.php'; ?>

<div class="row">
    <div class="card" style="max-width: 400px; margin: 2rem auto;">
        <h2 style="text-align: center; margin-bottom: 2rem;">Login</h2>
        <form action="/auth/login" method="POST">
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
        </form>
        <p style="text-align: center; margin-top: 1rem;">
            Belum punya akun? <a href="/auth/register" style="color: var(--primary-color);">Register</a>
        </p>
    </div>
</div>

<?php require_once APPROOT . '/views/includes/footer.php'; ?> 