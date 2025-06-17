<div class="row">
    <div class="card">
        <h2 style="margin-bottom: 1.5rem;">Daftar Lensa Tersedia</h2>
        <div class="row">
            <?php foreach ($lenses as $lens): ?>
                <div class="lens-card card">
                    <img src="<?= $lens->image_url ?>" alt="<?= $lens->name ?>">
                    <div class="lens-price">Rp <?= number_format($lens->price_per_day) ?>/hari</div>
                    <div class="lens-info">
                        <h3><?= $lens->name ?></h3>
                        <p><?= $lens->description ?></p>
                        <div style="margin: 1rem 0;">
                            <span class="badge badge-success">Tersedia</span>
                        </div>
                        <form action="/rental/rent" method="POST">
                            <input type="hidden" name="lens_id" value="<?= $lens->id ?>">
                            <div class="form-group">
                                <label for="return_date" class="form-label">Tanggal Kembali</label>
                                <input type="date" id="return_date" name="return_date" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%;">Rental Sekarang</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div> 