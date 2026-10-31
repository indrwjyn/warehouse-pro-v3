<section class="page-head">
    <div>
        <span class="eyebrow">BinderByte API</span>
        <h1>Tracking Resi</h1>
        <p>Cek status pengiriman multi ekspedisi via BinderByte API.</p>
    </div>
</section>

<section class="row g-4">
    <div class="col-lg-5">
        <div class="panel form-panel">
            <div class="panel-title">
                <div>
                    <h2>Cek Pengiriman</h2>
                    <p>Pilih ekspedisi lalu masukkan nomor AWB / resi.</p>
                </div>
            </div>
            <form id="trackingForm" class="needs-live-validation" novalidate>
                <div class="mb-3">
                    <label class="form-label">Kurir</label>
                    <select id="courier" class="form-select" required>
                        <option value="auto">Deteksi Otomatis</option>
                        <?php foreach (tracking_couriers() as $code => $label): ?>
                            <option value="<?php echo htmlspecialchars($code); ?>"><?php echo htmlspecialchars($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Pilih ekspedisi.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nomor Resi (AWB)</label>
                    <input type="text" id="awb" class="form-control" placeholder="Masukkan nomor resi" required>
                    <div class="invalid-feedback">Nomor resi wajib diisi.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">5 Digit Terakhir No. HP</label>
                    <input type="text" id="trackingNumber" class="form-control" maxlength="5" inputmode="numeric" pattern="[0-9]{5}" placeholder="Opsional, wajib untuk sebagian JNE">
                    <div class="invalid-feedback">Isi tepat 5 digit angka.</div>
                </div>
                <button type="submit" class="btn btn-electric w-100"><i class="fa-solid fa-magnifying-glass-location"></i> Cek Resi</button>
            </form>
            <div id="trackingError" class="alert alert-danger mt-3 d-none"></div>
        </div>
    </div>
    <div class="col-lg-7">
        <div id="trackingResult" class="tracking-empty">
            <i class="fa-solid fa-route"></i>
            <strong>Timeline pengiriman akan tampil di sini.</strong>
            <span>Data berasal dari respons JSON BinderByte API.</span>
        </div>
    </div>
</section>
