<div class="row">
    <div class="card">
        <h2 style="margin-bottom: 1.5rem;">Daftar Lensa yang Dirental</h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Lensa</th>
                        <th>Tanggal Rental</th>
                        <th>Tanggal Kembali</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rentals as $rental): ?>
                        <tr>
                            <td><?= $rental->lens_name ?></td>
                            <td><?= date('d/m/Y', strtotime($rental->rental_date)) ?></td>
                            <td><?= date('d/m/Y', strtotime($rental->return_date)) ?></td>
                            <td>
                                <?php if ($rental->status === 'active'): ?>
                                    <span class="badge badge-warning">Aktif</span>
                                <?php elseif ($rental->status === 'returned'): ?>
                                    <span class="badge badge-success">Dikembalikan</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Terlambat</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($rental->status === 'active'): ?>
                                    <form action="/return/process" method="POST" style="display: inline;">
                                        <input type="hidden" name="rental_id" value="<?= $rental->id ?>">
                                        <button type="submit" class="btn btn-primary">Kembalikan</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div> 