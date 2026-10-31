document.addEventListener('DOMContentLoaded', function () {
    const toastEl = document.getElementById('appToast');
    const toast = toastEl ? new bootstrap.Toast(toastEl, { delay: 2600 }) : null;

    function showToast(message) {
        if (!toastEl || !toast) {
            alert(message);
            return;
        }
        toastEl.querySelector('.toast-body').textContent = message;
        toast.show();
    }

    function markInvalid(field, message) {
        field.classList.add('is-invalid', 'shake');
        const feedback = field.parentElement.querySelector('.invalid-feedback');
        if (feedback && message) feedback.textContent = message;
        setTimeout(() => field.classList.remove('shake'), 450);
    }

    document.querySelectorAll('.needs-live-validation').forEach((form) => {
        form.querySelectorAll('[required]').forEach((field) => {
            field.addEventListener('input', () => field.classList.toggle('is-invalid', !field.value.trim()));
            field.addEventListener('change', () => field.classList.toggle('is-invalid', !field.value.trim()));
        });

        form.addEventListener('submit', (event) => {
            let valid = true;
            form.querySelectorAll('[required]').forEach((field) => {
                if (field.closest('.d-none')) return;
                if (!field.value.trim()) {
                    valid = false;
                    markInvalid(field, 'Field ini wajib diisi.');
                }
            });
            if (!valid) {
                event.preventDefault();
                showToast('Lengkapi semua field wajib sebelum menyimpan.');
            }
        });
    });

    const inventoryForm = document.getElementById('inventoryForm');
    if (inventoryForm) {
        const productMap = window.productStockMap || {};
        const productInput = document.getElementById('product_id');
        const locationInput = document.getElementById('storage_location_id');
        const qtyInput = document.getElementById('quantity');
        const moveIn = document.getElementById('moveIn');
        const moveOut = document.getElementById('moveOut');

        function syncMovementFields() {
            const isOut = moveOut.checked;
            document.querySelector('.movement-in').classList.toggle('d-none', isOut);
            document.querySelector('.movement-out').classList.toggle('d-none', !isOut);
            document.querySelector('.date-in').classList.toggle('d-none', isOut);
            document.querySelector('.date-out').classList.toggle('d-none', !isOut);

            inventoryForm.querySelector('[name="vendor"]').toggleAttribute('required', !isOut);
            inventoryForm.querySelector('[name="sender_name"]').toggleAttribute('required', !isOut);
            inventoryForm.querySelector('[name="entry_date"]').toggleAttribute('required', !isOut);
            inventoryForm.querySelector('[name="courier"]').toggleAttribute('required', isOut);
            inventoryForm.querySelector('[name="exit_date"]').toggleAttribute('required', isOut);
        }

        productInput.addEventListener('change', () => {
            const option = productInput.selectedOptions[0];
            if (option && option.dataset.location) locationInput.value = option.dataset.location;
        });

        [moveIn, moveOut].forEach((radio) => radio.addEventListener('change', syncMovementFields));
        syncMovementFields();

        inventoryForm.addEventListener('submit', (event) => {
            const quantity = parseInt(qtyInput.value, 10);
            const product = productMap[productInput.value];
            if (moveOut.checked && product && quantity > parseInt(product.current_stock, 10)) {
                event.preventDefault();
                markInvalid(qtyInput, 'Jumlah keluar melebihi stok saat ini.');
                showToast('Qty OUT tidak boleh melebihi stok produk.');
            }
        });
    }

    const searchInput = document.getElementById('productSearch');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function () {
            fetch('modules/product_search.php?term=' + encodeURIComponent(this.value.trim()))
                .then((response) => response.json())
                .then((data) => {
                    const tableBody = document.getElementById('productTableBody');
                    if (!tableBody) return;
                    tableBody.innerHTML = data.map((item) => `
                        <tr>
                            <td><div class="product-cell"><img src="${escapeHtml(item.image_url)}" alt="${escapeHtml(item.name)}"><div><strong>${escapeHtml(item.name)}</strong><small>${escapeHtml(item.unit)}</small></div></div></td>
                            <td>${escapeHtml(item.sku)}</td>
                            <td>${escapeHtml(item.warehouse_name)} / ${escapeHtml(item.room_name)} / ${escapeHtml(item.rack_number)}</td>
                            <td><span class="stock-pill">${item.current_stock}</span></td>
                            <td>${item.stock_min}</td>
                            <td><a href="index.php?page=produk&action=edit&id=${item.id}" class="btn btn-sm btn-primary"><i class="fa-solid fa-pen"></i></a></td>
                        </tr>
                    `).join('');
                });
        }, 280));
    }

    const productImageInput = document.querySelector('input[name="product_image"]');
    if (productImageInput) {
        productImageInput.addEventListener('change', function () {
            const file = this.files && this.files[0];
            const preview = document.querySelector('.product-image-preview img');
            if (!file || !preview) return;

            const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (!allowed.includes(file.type)) {
                this.value = '';
                showToast('Format gambar harus JPG, PNG, WebP, atau GIF.');
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                this.value = '';
                showToast('Ukuran gambar maksimal 2MB.');
                return;
            }

            preview.src = URL.createObjectURL(file);
        });
    }

    const trackingForm = document.getElementById('trackingForm');
    if (trackingForm) {
        const awbInput = document.getElementById('awb');
        const courierInput = document.getElementById('courier');
        const trackingNumberInput = document.getElementById('trackingNumber');
        const resultContainer = document.getElementById('trackingResult');
        const trackingError = document.getElementById('trackingError');

        if (trackingNumberInput) {
            trackingNumberInput.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '').slice(0, 5);
            });
        }

        trackingForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const awb = awbInput.value.trim();
            const courier = courierInput ? courierInput.value : 'jnt';
            const number = trackingNumberInput ? trackingNumberInput.value.trim() : '';
            if (!awb) {
                showTrackingError('Nomor resi wajib diisi.');
                showToast('Masukkan nomor resi terlebih dahulu.');
                return;
            }
            if (number && !/^\d{5}$/.test(number)) {
                showTrackingError('5 digit terakhir nomor HP harus tepat 5 angka.');
                showToast('Periksa 5 digit nomor HP.');
                return;
            }

            showTrackingError('');
            resultContainer.className = 'panel loading-panel';
            resultContainer.innerHTML = '<div class="spinner-border text-warning" role="status"></div><strong>Mengambil data tracking...</strong>';

            fetch('modules/tracking_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ courier, awb, number })
            })
                .then((response) => response.json())
                .then((data) => {
                    if (!data || data.status !== 200 || !data.data) {
                        throw new Error(data.message || 'Data resi tidak ditemukan.');
                    }
                    renderTrackingResult(data.data, data.detected_courier_name || '');
                })
                .catch((error) => {
                    showTrackingError(error.message || 'Terjadi kesalahan saat memanggil API.');
                    resultContainer.className = 'tracking-empty';
                    resultContainer.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i><strong>Belum ada timeline.</strong><span>Coba kurir dan AWB lain.</span>';
                });
        });

        function showTrackingError(message) {
            trackingError.textContent = message;
            trackingError.classList.toggle('d-none', !message);
            trackingError.classList.toggle('shake', !!message);
        }

        function renderTrackingResult(data, detectedCourier) {
            const summary = data.summary || {};
            const detail = data.detail || {};
            const history = (data.history || []).filter((item) => item && !Array.isArray(item) && (item.desc || item.date || item.location));
            const courierName = detectedCourier || summary.courier || '-';
            resultContainer.className = 'panel tracking-panel fade-in';
            resultContainer.innerHTML = `
                <div class="tracking-summary">
                    <span><i class="fa-solid fa-truck-fast"></i></span>
                    <div>
                        <h2>${escapeHtml(courierName.toUpperCase())} - ${escapeHtml(summary.awb || summary.waybill || '-')}</h2>
                        <p>${escapeHtml(summary.status || 'Status tidak tersedia')} - ${escapeHtml(summary.service || '-')}</p>
                    </div>
                </div>
                <div class="tracking-detail">
                    <span><strong>Asal:</strong> ${escapeHtml(detail.origin || '-')}</span>
                    <span><strong>Tujuan:</strong> ${escapeHtml(detail.destination || '-')}</span>
                    <span><strong>Pengirim:</strong> ${escapeHtml(detail.shipper || '-')}</span>
                    <span><strong>Penerima:</strong> ${escapeHtml(detail.receiver || '-')}</span>
                </div>
                <ul class="timeline">
                    ${history.length ? history.map((item) => `
                        <li class="timeline-item">
                            <h3>${escapeHtml(item.desc || '-')}</h3>
                            <span>${escapeHtml(item.date || '')}${item.location ? ' - ' + escapeHtml(item.location) : ''}</span>
                        </li>
                    `).join('') : '<li class="timeline-item"><h3>Belum ada detail perjalanan dari ekspedisi.</h3><span>Status ringkasan sudah diterima dari BinderByte.</span></li>'}
                </ul>
            `;
        }
    }

    function debounce(callback, delay) {
        let timer;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => callback.apply(this, args), delay);
        };
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[char]));
    }
});
