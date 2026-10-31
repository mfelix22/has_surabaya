@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <style>
        .vendor-card {
            border: 2px solid #dee2e6;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .vendor-card .vendor-header {
            background: #f8f9fa;
            padding: 10px 15px;
            border-bottom: 1px solid #dee2e6;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .vendor-card .vendor-body {
            padding: 15px;
        }

        #vendor-list>.vendor-wrapper:first-child .btn-remove-vendor {
            display: none;
        }
    </style>
@endpush

@section('title', 'Buat Formulir Perbandingan Vendor')
@section('page_title', 'Buat Formulir Perbandingan Vendor')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-balance-scale mr-2"></i>
                        Formulir Perbandingan Vendor (FK-PCH)
                    </h3>
                </div>

                <form action="{{ route('vendor_comparisons.store') }}" method="POST" id="vc-form">
                    @csrf
                    <div class="card-body">

                        {{-- General Info --}}
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle"></i>
                            Nomor formulir akan di-generate secara otomatis. Isi minimal 1 vendor, maksimal 3 vendor.
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="purchase_request_id">Link ke PPB / PPJ <small
                                            class="text-muted">(Opsional)</small></label>
                                    <select name="purchase_request_id" id="purchase_request_id"
                                        class="form-control select2">
                                        <option value="">-- Pilih PPB/PPJ --</option>
                                        @foreach ($prs as $pr)
                                            <option value="{{ $pr->id }}"
                                                {{ old('purchase_request_id', $selectedPrId) == $pr->id ? 'selected' : '' }}>
                                                {{ $pr->pr_number }} ({{ $pr->type }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tanggal">Tanggal <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal" id="tanggal"
                                        class="form-control @error('tanggal') is-invalid @enderror"
                                        value="{{ old('tanggal', date('Y-m-d')) }}" required>
                                    @error('tanggal')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Items selection from PR --}}
                        <div id="pr-items-section" style="display:none" class="mb-3">
                            <label><strong>Pilih Item dari PPB/PPJ:</strong></label>
                            <div id="pr-items-list" class="border rounded p-3 bg-light">
                                <span class="text-muted">Pilih PPB/PPJ terlebih dahulu.</span>
                            </div>
                            <small class="text-muted">Centang item yang ingin dimasukkan dalam perbandingan
                                vendor.</small>
                        </div>

                        {{-- Item comparison table --}}
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="mb-0"><strong>Item yang Dibandingkan:</strong> <span
                                        class="text-danger">*</span></label>
                                <button type="button" id="btn-add-manual-item" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-plus"></i> Tambah Item Manual
                                </button>
                            </div>
                            <table class="table table-sm table-bordered mb-1" id="items-table" style="display:none">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Nama Item</th>
                                        <th style="width:120px">Qty</th>
                                        <th style="width:120px">Satuan</th>
                                        <th style="width:50px"></th>
                                    </tr>
                                </thead>
                                <tbody id="items-table-body"></tbody>
                            </table>
                            <div id="items-empty-msg" class="text-muted small">Belum ada item. Pilih PPB/PPJ atau
                                tambah item manual.</div>
                            @error('items')
                                <span class="text-danger small d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <input type="hidden" name="detail_barang_jasa" id="detail_barang_jasa" value="{{ old('detail_barang_jasa') }}">

                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0"><i class="fas fa-building mr-1"></i> Data Calon Vendor</h5>
                            <button type="button" id="btn-add-vendor" class="btn btn-success btn-sm">
                                <i class="fas fa-plus"></i> Tambah Vendor
                            </button>
                        </div>

                        <div id="vendor-list">
                            {{-- Vendor 1 (always present) --}}
                            <div class="vendor-wrapper" data-index="0">
                                @include('vendor_comparisons._vendor_form', [
                                    'index' => 0,
                                    'vendors' => $suppliers,
                                    'vendorNum' => 1,
                                ])
                            </div>
                        </div>

                        <hr>
                        <div class="form-group">
                            <label for="notes">Catatan / Note</label>
                            <textarea name="notes" id="notes" rows="2" class="form-control" placeholder="Catatan tambahan...">{{ old('notes') }}</textarea>
                        </div>

                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Formulir
                        </button>
                        <a href="{{ route('vendor_comparisons.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        const MAX_VENDORS = 3;

        // Pre-build supplier data for JS use
        const suppliersData = @json($suppliers);

        // ---------------------------------------------------------------
        // Item comparison state — shared source of truth for the item
        // list table and every vendor's per-item price table.
        // ---------------------------------------------------------------
        let itemsState = [];
        let itemUidCounter = 1;

        function escapeHtml(str) {
            return String(str ?? '').replace(/[&<>"']/g, c => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [c]));
        }

        function formatRupiah(amount) {
            return 'Rp ' + amount.toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function addItem(name, qty, uom, prDetailId) {
            const uid = 'i' + (itemUidCounter++);
            itemsState.push({
                uid,
                name: name || '',
                qty: qty || '',
                uom: uom || '',
                pr_detail_id: prDetailId || ''
            });
            renderItemsTable();
            renderAllVendorPriceTables();
            return uid;
        }

        function removeItem(uid) {
            itemsState = itemsState.filter(it => it.uid !== uid);
            renderItemsTable();
            renderAllVendorPriceTables();
        }

        function syncDetailBarangJasa() {
            document.getElementById('detail_barang_jasa').value = itemsState
                .map(it => it.name).filter(Boolean).join('\n');
        }

        function renderItemsTable() {
            const table = document.getElementById('items-table');
            const tbody = document.getElementById('items-table-body');
            const emptyMsg = document.getElementById('items-empty-msg');

            tbody.innerHTML = itemsState.map(it => `
                <tr data-uid="${it.uid}">
                    <td>
                        <input type="text" class="form-control form-control-sm" name="items[${it.uid}][name]"
                            value="${escapeHtml(it.name)}" placeholder="Nama item" ${it.pr_detail_id ? 'readonly' : ''} required>
                        <input type="hidden" name="items[${it.uid}][pr_detail_id]" value="${it.pr_detail_id || ''}">
                    </td>
                    <td><input type="number" step="0.01" min="0" class="form-control form-control-sm"
                        name="items[${it.uid}][qty]" value="${escapeHtml(it.qty)}" ${it.pr_detail_id ? 'readonly' : ''}></td>
                    <td><input type="text" class="form-control form-control-sm"
                        name="items[${it.uid}][uom]" value="${escapeHtml(it.uom)}" ${it.pr_detail_id ? 'readonly' : ''}></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-item" data-uid="${it.uid}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');

            table.style.display = itemsState.length ? '' : 'none';
            emptyMsg.style.display = itemsState.length ? 'none' : '';

            syncDetailBarangJasa();
        }

        function renderVendorPriceTable(wrapper, presetValues) {
            const index = wrapper.dataset.index;
            const body = wrapper.querySelector('.vendor-price-body');
            if (!body) return;

            const existing = presetValues || {};
            if (!presetValues) {
                body.querySelectorAll('.vendor-price-input').forEach(inp => {
                    existing[inp.dataset.uid] = inp.value;
                });
            }

            if (itemsState.length === 0) {
                body.innerHTML = '<tr><td colspan="2" class="text-muted text-center">Belum ada item untuk dibandingkan.</td></tr>';
            } else {
                body.innerHTML = itemsState.map(it => `
                    <tr>
                        <td>${escapeHtml(it.name) || '<em class="text-muted">(nama item belum diisi)</em>'}</td>
                        <td>
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm vendor-price-input"
                                data-uid="${it.uid}" name="vendors[${index}][prices][${it.uid}]"
                                value="${existing[it.uid] !== undefined ? existing[it.uid] : ''}" placeholder="0">
                        </td>
                    </tr>
                `).join('');
            }

            updateVendorSubtotal(wrapper);
        }

        function renderAllVendorPriceTables() {
            document.querySelectorAll('#vendor-list .vendor-wrapper').forEach(wrapper => renderVendorPriceTable(
                wrapper));
        }

        function updateVendorSubtotal(wrapper) {
            const subtotalInput = wrapper.querySelector('.vendor-subtotal');
            const totalInput = wrapper.querySelector('.total-ppn');
            const includeCheckbox = wrapper.querySelector('[name*="include_ppn"]');

            let subtotal = 0;
            wrapper.querySelectorAll('.vendor-price-input').forEach(inp => {
                subtotal += parseFloat(inp.value) || 0;
            });

            if (subtotalInput) subtotalInput.value = subtotal ? formatRupiah(subtotal) : '';
            const total = includeCheckbox && includeCheckbox.checked ? subtotal * 1.11 : subtotal;
            if (totalInput) totalInput.value = subtotal ? formatRupiah(total) : '';
        }

        function buildVendorForm(index, num) {
            return `
            <div class="vendor-wrapper" data-index="${index}">
                <div class="vendor-card">
                    <div class="vendor-header">
                        <span><i class="fas fa-store mr-1"></i> Vendor ${num}</span>
                        <button type="button" class="btn btn-danger btn-sm btn-remove-vendor">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </div>
                    <div class="vendor-body">
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <label class="text-muted small">
                                    <i class="fas fa-magic"></i>
                                    Auto-isi dari Supplier Master <em>(opsional — ketik manual di bawah)</em>
                                </label>
                                <select class="form-control select2 select2-supplier supplier-autofill" data-index="${index}">
                                    <option value="">-- Pilih Supplier untuk Auto-Isi --</option>
                                    ${suppliersData.map(s =>
                                        `<option
                                                                        data-name="${s.name}"
                                                                        data-alamat="${s.address || ''}"
                                                                        data-telepon="${s.phone || ''}"
                                                                        data-email="${s.email || ''}"
                                                                        data-pic="${s.contact_person || ''}"
                                                                        data-bank="${[s.bank_name, s.bank_account_no, s.bank_account_name ? 'a.n. '+s.bank_account_name : ''].filter(Boolean).join(' - ')}"
                                                                        value="${s.id}">${s.name}</option>`
                                    ).join('')}
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nama Calon Vendor <span class="text-danger">*</span></label>
                                    <input type="text" name="vendors[${index}][nama_calon_vendor]"
                                        class="form-control vendor-nama" placeholder="Nama Perusahaan / Vendor" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Alamat</label>
                                    <input type="text" name="vendors[${index}][alamat]"
                                        class="form-control vendor-alamat" placeholder="Alamat lengkap">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Telepon / Fax</label>
                                    <input type="text" name="vendors[${index}][telepon_fax]"
                                        class="form-control vendor-telepon" placeholder="e.g., 021-12345678">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="vendors[${index}][email]"
                                        class="form-control vendor-email" placeholder="email@vendor.com">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>PIC / Contact Person</label>
                                    <input type="text" name="vendors[${index}][pic_contact_person]"
                                        class="form-control vendor-pic" placeholder="Nama PIC">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Metode Pembayaran</label>
                                    <select name="vendors[${index}][metode_pembayaran]" class="form-control">
                                        <option value="">-- Pilih --</option>
                                        <option value="Tunai">Tunai</option>
                                        <option value="Kredit">Kredit</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Rekening Bank</label>
                                    <input type="text" name="vendors[${index}][rekening_bank]"
                                        class="form-control vendor-bank" placeholder="e.g., BCA - 1234567890 a.n. PT XYZ">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Term of Payment</label>
                                    <input type="text" name="vendors[${index}][term_of_payment]"
                                        class="form-control" placeholder="e.g., 30 hari">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <label>Rincian Harga per Item <span class="text-danger">*</span></label>
                                <table class="table table-sm table-bordered mb-1">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Item</th>
                                            <th style="width:180px">Harga (Rp)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="vendor-price-body"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Ketentuan Lain-lain dari Calon Supplier</label>
                                    <textarea name="vendors[${index}][ketentuan_lain]"
                                        class="form-control" rows="2"
                                        placeholder="Ketentuan lain dari calon supplier..."></textarea>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Subtotal</label>
                                    <input type="text" class="form-control vendor-subtotal" readonly placeholder="Rp 0">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Total Termasuk PPN 11%</label>
                                    <input type="text" class="form-control total-ppn" readonly placeholder="Rp 0">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="vendors[${index}][include_ppn]"
                                            id="include_ppn_${index}" class="custom-control-input" value="1" checked>
                                        <label class="custom-control-label" for="include_ppn_${index}">
                                            Harga sudah termasuk PPN 11%
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
        }

        function updateVendorHeaders() {
            document.querySelectorAll('#vendor-list .vendor-wrapper').forEach((wrapper, i) => {
                const header = wrapper.querySelector('.vendor-header span');
                if (header) header.innerHTML = `<i class="fas fa-store mr-1"></i> Vendor ${i + 1}`;
            });
        }

        function supplierAutofillHandler() {
            const $select = $(this);
            const opt = $select.find('option:selected');
            const wrapper = $select.closest('.vendor-wrapper')[0];
            if (!$select.val()) return;
            wrapper.querySelector('.vendor-nama').value = opt.data('name') || '';
            wrapper.querySelector('.vendor-alamat').value = opt.data('alamat') || '';
            wrapper.querySelector('.vendor-telepon').value = opt.data('telepon') || '';
            wrapper.querySelector('.vendor-email').value = opt.data('email') || '';
            wrapper.querySelector('.vendor-pic').value = opt.data('pic') || '';
            wrapper.querySelector('.vendor-bank').value = opt.data('bank') || '';
        }

        document.getElementById('vendor-list').addEventListener('input', function(e) {
            if (e.target.classList.contains('vendor-price-input')) {
                updateVendorSubtotal(e.target.closest('.vendor-wrapper'));
            }
        });

        document.getElementById('vendor-list').addEventListener('change', function(e) {
            if (e.target.name && e.target.name.includes('include_ppn')) {
                updateVendorSubtotal(e.target.closest('.vendor-wrapper'));
            }
        });

        document.getElementById('btn-add-vendor').addEventListener('click', function() {
            const currentCount = document.querySelectorAll('#vendor-list .vendor-wrapper').length;
            if (currentCount >= MAX_VENDORS) {
                alert('Maksimal 3 vendor per formulir.');
                return;
            }
            const idx = Date.now(); // unique index
            const num = currentCount + 1;
            const div = document.createElement('div');
            div.innerHTML = buildVendorForm(idx, num);
            const wrapper = div.firstElementChild;
            document.getElementById('vendor-list').appendChild(wrapper);

            if (currentCount + 1 >= MAX_VENDORS) {
                this.disabled = true;
                this.classList.add('disabled');
            }

            renderVendorPriceTable(wrapper);
            initSupplierSelect2(wrapper);
            attachRemoveButtons();
        });

        function initSupplierSelect2(context = document) {
            $(context).find('.select2-supplier').each(function() {
                const $select = $(this);
                if ($select.data('select2')) return;
                $select.select2({
                    theme: 'bootstrap4',
                    placeholder: '-- Pilih Supplier untuk Auto-Isi --',
                    allowClear: true,
                    width: '100%'
                }).on('change.select2autofill', supplierAutofillHandler);
            });
        }

        function attachRemoveButtons() {
            document.querySelectorAll('.btn-remove-vendor').forEach(btn => {
                btn.removeEventListener('click', removeVendorHandler);
                btn.addEventListener('click', removeVendorHandler);
            });
        }

        function removeVendorHandler() {
            const wrappers = document.querySelectorAll('#vendor-list .vendor-wrapper');
            if (wrappers.length <= 1) {
                alert('Minimal 1 vendor diperlukan.');
                return;
            }
            this.closest('.vendor-wrapper').remove();
            updateVendorHeaders();
            // Re-enable add button
            const addBtn = document.getElementById('btn-add-vendor');
            addBtn.disabled = false;
            addBtn.classList.remove('disabled');
        }

        // PR items data passed from controller
        const prsItems = @json($prsJson);

        function renderPrItems(prId) {
            const section = document.getElementById('pr-items-section');
            const list = document.getElementById('pr-items-list');

            // Drop any items that came from a previously selected PR.
            itemsState = itemsState.filter(it => !it.pr_detail_id);

            if (!prId || !prsItems[prId] || prsItems[prId].length === 0) {
                section.style.display = 'none';
                list.innerHTML = '<span class="text-muted">Tidak ada item pada PPB/PPJ ini.</span>';
                renderItemsTable();
                renderAllVendorPriceTables();
                return;
            }

            const items = prsItems[prId];
            section.style.display = '';

            let html = '';
            items.forEach(function(item) {
                const label = item.code ?
                    `${item.code} — ${item.name}` :
                    `${item.name}`;
                html += `
                <div class="form-check mb-1">
                    <input class="form-check-input pr-item-check" type="checkbox"
                        data-pr-detail-id="${item.id}" data-name="${escapeHtml(label)}"
                        data-qty="${item.qty}" data-uom="${escapeHtml(item.uom)}"
                        id="pr_item_${item.id}" checked>
                    <label class="form-check-label" for="pr_item_${item.id}">${label} (${item.qty} ${item.uom})</label>
                </div>`;
            });
            list.innerHTML = html;

            // Add all checked items to the comparison table by default.
            list.querySelectorAll('.pr-item-check:checked').forEach(cb => {
                addItem(cb.dataset.name, cb.dataset.qty, cb.dataset.uom, cb.dataset.prDetailId);
            });
        }

        document.getElementById('pr-items-list').addEventListener('change', function(e) {
            if (!e.target.classList.contains('pr-item-check')) return;
            const prDetailId = e.target.dataset.prDetailId;
            if (e.target.checked) {
                addItem(e.target.dataset.name, e.target.dataset.qty, e.target.dataset.uom, prDetailId);
            } else {
                const existing = itemsState.find(it => it.pr_detail_id === prDetailId);
                if (existing) removeItem(existing.uid);
            }
        });

        document.getElementById('btn-add-manual-item').addEventListener('click', function() {
            addItem('', '', '', '');
        });

        document.getElementById('items-table-body').addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-remove-item');
            if (!btn) return;
            const uid = btn.dataset.uid;
            const item = itemsState.find(it => it.uid === uid);
            if (item && item.pr_detail_id) {
                const cb = document.getElementById('pr_item_' + item.pr_detail_id);
                if (cb) cb.checked = false;
            }
            removeItem(uid);
        });

        document.getElementById('items-table-body').addEventListener('input', function(e) {
            const row = e.target.closest('tr');
            if (!row) return;
            const item = itemsState.find(it => it.uid === row.dataset.uid);
            if (!item) return;
            if (e.target.name.includes('[name]')) item.name = e.target.value;
            if (e.target.name.includes('[qty]')) item.qty = e.target.value;
            if (e.target.name.includes('[uom]')) item.uom = e.target.value;
            syncDetailBarangJasa();
            renderAllVendorPriceTables();
        });

        // Populate items on PR change (use jQuery for Select2 compatibility)
        $('#purchase_request_id').on('change', function() {
            renderPrItems(this.value);
        });

        // On form submit: make sure at least one item has been added.
        document.getElementById('vc-form').addEventListener('submit', function(e) {
            if (itemsState.length === 0) {
                e.preventDefault();
                alert('Tambahkan minimal 1 item untuk dibandingkan.');
                return;
            }
            syncDetailBarangJasa();
        });

        // Initialize on page load (if PR pre-selected via query string)
        (function() {
            const prSelect = document.getElementById('purchase_request_id');
            if (prSelect.value) {
                renderPrItems(prSelect.value);
            }
        })();

        // Initialize vendor form handlers
        attachRemoveButtons();
        renderItemsTable();
        renderAllVendorPriceTables();

        // Initialize select2 dropdowns
        initSupplierSelect2();
        $('#purchase_request_id').select2({
            theme: 'bootstrap4',
            placeholder: '-- Pilih PPB/PPJ --',
            allowClear: true,
            width: '100%'
        });
    </script>
@endpush
