@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endpush

@section('title', 'Edit Work Order')
@section('page_title', 'Edit Work Order')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $workOrder->wo_number }}</h3>
                </div>

                <form action="{{ route('work_orders.update', $workOrder) }}" method="POST" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <strong>Please fix the following errors:</strong>
                                <ul class="mb-0 mt-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <div class="row">
                            {{-- Col 1: Basic Info --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="wo_number">WO Number <span class="text-danger">*</span></label>
                                    <input type="text" name="wo_number" id="wo_number"
                                        class="form-control @error('wo_number') is-invalid @enderror"
                                        value="{{ old('wo_number', $workOrder->wo_number) }}" required>
                                    @error('wo_number')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="account_code">Account Code <span class="text-danger">*</span></label>
                                    <select name="account_code" id="account_code" class="form-control select2" required>
                                        <option value="C"
                                            {{ old('account_code', $workOrder->account_code) === 'C' ? 'selected' : '' }}>
                                            Cash</option>
                                        <option value="INT_WS"
                                            {{ old('account_code', $workOrder->account_code) === 'INT_WS' ? 'selected' : '' }}>
                                            Internal WS</option>
                                        <option value="INT_W3"
                                            {{ old('account_code', $workOrder->account_code) === 'INT_W3' ? 'selected' : '' }}>
                                            Internal W3</option>
                                    </select>
                                </div>

                                {{-- Reference WO (visible only when INT_W3) --}}
                                <div class="form-group" id="reference_wo_group" style="display:none;">
                                    <label for="reference_wo_id">Reference Work Order <span
                                            class="text-danger">*</span></label>
                                    <select name="reference_wo_id" id="reference_wo_id"
                                        class="form-control select2 @error('reference_wo_id') is-invalid @enderror"
                                        style="width:100%">
                                        <option value="">-- Select Reference WO --</option>
                                        @foreach ($completedWos as $refWo)
                                            <option value="{{ $refWo->id }}"
                                                {{ old('reference_wo_id', $workOrder->reference_wo_id) == $refWo->id ? 'selected' : '' }}>
                                                {{ $refWo->wo_number }} — {{ $refWo->customer->name ?? '-' }} —
                                                {{ $refWo->paket_name ?? '-' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('reference_wo_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="customer_id">Customer <span class="text-danger">*</span></label>
                                    <select name="customer_id" id="customer_id" class="form-control select2" required>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}"
                                                {{ $workOrder->customer_id == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="billing_customer_id">Ditujukan Kepada
                                        <small class="text-muted">(opsional — nama customer di Invoice)</small>
                                    </label>
                                    <select name="billing_customer_id" id="billing_customer_id"
                                        class="form-control select2 @error('billing_customer_id') is-invalid @enderror">
                                        <option value="">-- Sama dengan Customer --</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}"
                                                {{ old('billing_customer_id', $workOrder->billing_customer_id) == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('billing_customer_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="work_date">Work Date <span class="text-danger">*</span></label>
                                    <input type="date" name="work_date" id="work_date" class="form-control"
                                        value="{{ old('work_date', $workOrder->work_date->format('Y-m-d')) }}" required>
                                </div>
                                <div class="form-group">
                                    <label for="deadline">Deadline</label>
                                    <input type="date" name="deadline" id="deadline" class="form-control"
                                        value="{{ old('deadline', $workOrder->deadline?->format('Y-m-d')) }}">
                                </div>
                                <div class="form-group">
                                    <label for="sa_sales">SA / Sales</label>
                                    <input type="text" name="sa_sales" id="sa_sales" class="form-control"
                                        placeholder="Nama SA/Sales" value="{{ old('sa_sales', $workOrder->sa_sales) }}">
                                </div>
                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea name="notes" id="notes" class="form-control" rows="2">{{ old('notes', $workOrder->notes) }}</textarea>
                                </div>
                            </div>

                            {{-- Col 2: Vehicle Info --}}
                            <div class="col-md-4">
                                <h6><i class="fas fa-car"></i> Vehicle Info</h6>
                                <div class="form-group">
                                    <label for="vehicle_plate">Licence Plate</label>
                                    <input type="text" name="vehicle_plate" id="vehicle_plate" class="form-control"
                                        placeholder="e.g., W 1988 MR"
                                        value="{{ old('vehicle_plate', $workOrder->vehicle_plate) }}">
                                </div>
                                <div class="form-group">
                                    <label for="vehicle_merk">Merk (Brand)</label>
                                    <input type="text" name="vehicle_merk" id="vehicle_merk" class="form-control"
                                        placeholder="e.g., Mercedes-Benz"
                                        value="{{ old('vehicle_merk', $workOrder->vehicle_merk) }}">
                                </div>
                                <div class="form-group">
                                    <label for="vehicle_type_year">Type / Year</label>
                                    <input type="text" name="vehicle_type_year" id="vehicle_type_year"
                                        class="form-control" placeholder="e.g., E 350 / 2019"
                                        value="{{ old('vehicle_type_year', $workOrder->vehicle_type_year) }}">
                                </div>
                                <div class="form-group">
                                    <label for="vehicle_km">Mileage KM</label>
                                    <div class="input-group">
                                        <input type="number" name="vehicle_km" id="vehicle_km" class="form-control"
                                            min="0" value="{{ old('vehicle_km', $workOrder->vehicle_km) }}">
                                        <div class="input-group-append"><span class="input-group-text">KM</span></div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="chasis_no">Chasis No</label>
                                    <input type="text" name="chasis_no" id="chasis_no" class="form-control"
                                        placeholder="e.g., MHL213085KJ001691"
                                        value="{{ old('chasis_no', $workOrder->chasis_no) }}">
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="save_to_vehicle_master"
                                            name="save_to_vehicle_master" value="1"
                                            {{ old('save_to_vehicle_master', $workOrder->vehicle_id ? '1' : '') ? 'checked' : '' }}>
                                        <label class="custom-control-label font-weight-bold" for="save_to_vehicle_master">
                                            <i class="fas fa-save mr-1 text-success"></i>Save vehicle to master data
                                        </label>
                                    </div>
                                    <small class="text-muted ml-4">
                                        @if ($workOrder->vehicle_id)
                                            <span class="text-success"><i class="fas fa-check-circle mr-1"></i>Already
                                                registered in master data.</span>
                                        @else
                                            Registers this vehicle so it can be selected in future Work Orders.
                                        @endif
                                    </small>
                                </div>
                                <div class="form-group">
                                    <label for="description">Work Description</label>
                                    <textarea name="description" id="description" class="form-control" rows="2">{{ old('description', $workOrder->description) }}</textarea>
                                </div>
                            </div>

                            {{-- Col 3: Paket --}}
                            <div class="col-md-4">
                                <h6><i class="fas fa-tags"></i> Paket HR Auto Studio 2026</h6>
                                <div class="form-group">
                                    <label>Pilih Paket</label>
                                    <select id="paket_select" class="form-control">
                                        <option value="">-- Pilih Paket --</option>
                                        @foreach ($packages as $category => $pkgs)
                                            <optgroup label="{{ $category }}">
                                                @foreach ($pkgs as $code => $pkg)
                                                    <option value="{{ $code }}" data-name="{{ $pkg['name'] }}"
                                                        data-category="{{ $category }}"
                                                        data-sizes='@json($pkg['sizes'])'
                                                        data-bom='@json($pkg['bom'] ?? [])'>
                                                        {{ $code }} &mdash; {{ $pkg['name'] }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group" id="size_field" style="display:none;">
                                    <label>Ukuran / Variant</label>
                                    <select id="size_select" class="form-control">
                                        <option value="">-- Pilih Ukuran --</option>
                                    </select>
                                </div>
                                <input type="hidden" name="paket_code" id="paket_code"
                                    value="{{ old('paket_code', $workOrder->paket_code) }}">
                                <input type="hidden" name="paket_name" id="paket_name"
                                    value="{{ old('paket_name', $workOrder->paket_name) }}">
                                <input type="hidden" name="paket_size" id="paket_size"
                                    value="{{ old('paket_size', $workOrder->paket_size) }}">
                                <input type="hidden" name="paket_grand_total" id="paket_grand_total"
                                    value="{{ old('paket_grand_total', $workOrder->paket_grand_total ?? 0) }}">

                                {{-- Add extra Ala-Carte / Coating packages on top of the main selection --}}
                                <div id="alacarte-section" style="display:none;margin-top:10px;">
                                    <label class="font-weight-bold text-primary"><i class="fas fa-plus-circle"></i>
                                        Add
                                        more Ala-Carte / Coating services:</label>
                                    <div class="input-group mb-1">
                                        <select id="alacarte-add-select" class="form-control form-control-sm">
                                            <option value="">-- Select service --</option>
                                        </select>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                id="alacarte-add-btn">
                                                <i class="fas fa-plus"></i> Add
                                            </button>
                                        </div>
                                    </div>
                                    <div id="alacarte-chips" class="mb-1"></div>
                                </div>

                                <div id="paket_price_display"
                                    style="{{ $workOrder->paket_grand_total ? '' : 'display:none;' }}">
                                    <table class="table table-sm table-bordered mt-2">
                                        <tr>
                                            <td>Jasa Paket</td>
                                            <td class="text-right"><strong id="display_material">Rp
                                                    {{ number_format(($workOrder->paket_grand_total ?? 0) - 75000, 0, ',', '.') }}</strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Labor (Paket)</td>
                                            <td class="text-right"><strong>Rp 75.000</strong></td>
                                        </tr>
                                        <tr id="display_addon_row"
                                            style="{{ $workOrder->labors->sum('total_price') > 0 ? '' : 'display:none;' }}">
                                            <td>Addon Labors</td>
                                            <td class="text-right"><strong id="display_addon_labor">Rp
                                                    {{ number_format($workOrder->labors->sum('total_price'), 0, ',', '.') }}</strong>
                                            </td>
                                        </tr>
                                        <tr class="table-success">
                                            <td><strong>Grand Total</strong></td>
                                            <td class="text-right text-success"><strong id="display_grand_total">Rp
                                                    {{ number_format(($workOrder->paket_grand_total ?? 0) + $workOrder->labors->sum('total_price'), 0, ',', '.') }}</strong>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h5><i class="fas fa-boxes"></i> Materials Used</h5>
                        <div id="items-container">
                            @foreach ($workOrder->items as $index => $item)
                                <div class="item-row card mb-2 border-left-primary">
                                    <div class="card-body py-2">
                                        <div class="row align-items-center">
                                            <div class="col-md-4">
                                                <div class="form-group mb-0">
                                                    <label><strong>Item</strong></label>
                                                    <select name="items[{{ $index }}][item_id]"
                                                        class="form-control item-select" required>
                                                        <option value="">Select Item</option>
                                                        @foreach ($items as $itemOption)
                                                            @php
                                                                $stock =
                                                                    (float) ($itemOption->stocks->sum('quantity') ?? 0);
                                                                $stockFormatted =
                                                                    $stock == floor($stock)
                                                                        ? number_format($stock, 0)
                                                                        : rtrim(
                                                                            rtrim(number_format($stock, 2), '0'),
                                                                            '.',
                                                                        );
                                                            @endphp
                                                            <option value="{{ $itemOption->id }}"
                                                                data-stock="{{ $stockFormatted }}"
                                                                data-uom="{{ $itemOption->smallestUom->code ?? '-' }}"
                                                                {{ $item->item_id == $itemOption->id ? 'selected' : '' }}>
                                                                [{{ $itemOption->code }}] {{ $itemOption->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <small class="form-text text-muted item-stock">Stock:
                                                        @php
                                                            $currentStock =
                                                                (float) ($items
                                                                    ->find($item->item_id)
                                                                    ?->stocks?->sum('quantity') ?? 0);
                                                            $stockDisplay =
                                                                $currentStock == floor($currentStock)
                                                                    ? number_format($currentStock, 0)
                                                                    : rtrim(
                                                                        rtrim(number_format($currentStock, 2), '0'),
                                                                        '.',
                                                                    );
                                                        @endphp
                                                        {{ $stockDisplay }}
                                                        {{ $items->find($item->item_id)?->smallestUom?->code }}</small>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label><strong>Demand Qty</strong></label>
                                                    <div class="input-group">
                                                        <input type="number"
                                                            name="items[{{ $index }}][demand_quantity]"
                                                            class="form-control qty" step="0.01" min="0.01"
                                                            value="{{ $item->demand_quantity }}" required>
                                                        <div class="input-group-append">
                                                            <span
                                                                class="input-group-text uom-display">{{ $items->find($item->item_id)?->smallestUom?->code ?? '-' }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group mb-0">
                                                    <label><strong>Remark</strong></label>
                                                    <input type="text" name="items[{{ $index }}][remark]"
                                                        class="form-control" value="{{ $item->remark }}"
                                                        placeholder="e.g., Bundling HRM, WIP 39780">
                                                </div>
                                            </div>

                                            <div class="col-md-2 mt-3">
                                                <button type="button" class="btn btn-danger btn-sm remove-item">
                                                    <i class="fas fa-trash"></i> Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-success btn-sm" id="add-item">
                            <i class="fas fa-plus"></i> Add Material
                        </button>

                        <hr>
                        <h5><i class="fas fa-user-tie"></i> Labor</h5>

                        <div id="labors-container">
                            @foreach ($workOrder->labors as $index => $labor)
                                <div class="labor-row card mb-2 border-left-success">
                                    <div class="card-body py-2">
                                        <div class="row align-items-end">
                                            <div class="col-md-6">
                                                <div class="form-group mb-0">
                                                    <label><strong>Labor Description</strong></label>
                                                    <input type="text" name="labors[{{ $index }}][description]"
                                                        class="form-control labor-description"
                                                        value="{{ $labor->description }}"
                                                        placeholder="Enter labor description">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label><strong>Qty</strong></label>
                                                    <input type="number" name="labors[{{ $index }}][qty]"
                                                        class="form-control labor-qty" step="1" min="1"
                                                        value="{{ $labor->qty ?? 1 }}">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group mb-0">
                                                    <label><strong>Remarks</strong></label>
                                                    <input type="text" name="labors[{{ $index }}][remarks]"
                                                        class="form-control" value="{{ $labor->remarks }}"
                                                        placeholder="Optional notes">
                                                </div>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-danger btn-sm remove-labor">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-success btn-sm" id="add-labor">
                            <i class="fas fa-plus"></i> Add Labor Entry
                        </button>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Update Work Order</button>
                        <a href="{{ route('work_orders.show', $workOrder) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let itemIndex = {{ $workOrder->items->count() }};
        let laborIndex = {{ $workOrder->labors->count() }};
        const LABOR_FIXED = 75000;

        // ===== PAKET SELECTOR =====
        const ALACARTE_CATEGORY = 'PAKET ALA-CARTE';
        const COATING_CATEGORY = 'PAKET COATING';
        // Categories whose services can be freely mixed & added as extras
        const MULTI_ADD_CATEGORIES = [ALACARTE_CATEGORY, COATING_CATEGORY];
        const paketSelect = document.getElementById('paket_select');
        const sizeSelect = document.getElementById('size_select');
        const sizeField = document.getElementById('size_field');
        const alacarteSection = document.getElementById('alacarte-section');
        const alacarteAddSel = document.getElementById('alacarte-add-select');
        const alacarteAddBtn = document.getElementById('alacarte-add-btn');
        const alacarteChips = document.getElementById('alacarte-chips');

        // Tracks all selected packages: [{code, name, size, price, bom}]
        let selectedPackages = [];

        // ---- Rebuild hidden inputs from selectedPackages ----
        // `sizes` is kept 1:1 aligned with `codes`/`names` (using 'All' as a
        // placeholder) so the WO print view can zip the three lists back together.
        function rebuildPrimaryFields() {
            if (!selectedPackages.length) return;
            const codes = selectedPackages.map(p => p.code);
            const names = selectedPackages.map(p => p.name);
            const total = selectedPackages.reduce((s, p) => s + parseFloat(p.price || 0), 0);
            const sizes = selectedPackages.map(p => p.size || 'All');
            document.getElementById('paket_code').value = codes.join(' + ');
            document.getElementById('paket_name').value = names.join(' + ');
            document.getElementById('paket_size').value = sizes.join(', ');
            document.getElementById('paket_grand_total').value = total;
            updatePriceDisplay(total);
        }

        // ---- Render chips for extras (index > 0) ----
        function renderChips() {
            alacarteChips.innerHTML = '';
            selectedPackages.slice(1).forEach(pkg => {
                const el = document.createElement('span');
                el.className = 'badge badge-primary mr-1 mb-1 p-2';
                el.style.fontSize = '12px';
                el.innerHTML =
                    `${pkg.code} &ndash; ${pkg.name}${pkg.size !== 'All' ? ' (' + pkg.size + ')' : ''}
                &nbsp; Rp ${pkg.price.toLocaleString('id-ID')}
                <a href="#" class="text-white ml-1" data-rm-pkg="${pkg.code}" style="text-decoration:none;">&times;</a>`;
                alacarteChips.appendChild(el);
            });
        }

        alacarteChips.addEventListener('click', function(e) {
            const a = e.target.closest('a[data-rm-pkg]');
            if (!a) return;
            e.preventDefault();
            const code = a.dataset.rmPkg;
            removeBomItemsByPkg(code);
            selectedPackages = selectedPackages.filter(p => p.code !== code);
            rebuildPrimaryFields();
            renderChips();
            buildAlacarteExtras();
        });

        // ---- Build the "Add extra" dropdown with unused Ala-Carte / Coating options ----
        function buildAlacarteExtras() {
            alacarteAddSel.innerHTML = '<option value="">-- Select service --</option>';
            const used = selectedPackages.map(p => p.code);
            const groups = {};
            MULTI_ADD_CATEGORIES.forEach(cat => groups[cat] = document.createElement('optgroup'));
            MULTI_ADD_CATEGORIES.forEach(cat => groups[cat].label = cat);
            Array.from(paketSelect.options).forEach(opt => {
                if (MULTI_ADD_CATEGORIES.includes(opt.dataset.category) && opt.value && !used.includes(opt.value)) {
                    groups[opt.dataset.category].appendChild(opt.cloneNode(true));
                }
            });
            MULTI_ADD_CATEGORIES.forEach(cat => {
                if (groups[cat].children.length) alacarteAddSel.appendChild(groups[cat]);
            });
            // Reset size picker
            const spDiv = document.getElementById('alacarte-size-div');
            if (spDiv) {
                spDiv.style.display = 'none';
                spDiv.innerHTML = '';
            }
        }

        // Dynamic size picker for extra services
        alacarteAddSel.addEventListener('change', function() {
            let spDiv = document.getElementById('alacarte-size-div');
            if (!spDiv) {
                spDiv = document.createElement('div');
                spDiv.id = 'alacarte-size-div';
                this.closest('.input-group').after(spDiv);
            }
            const opt = this.options[this.selectedIndex];
            if (!opt || !opt.value) {
                spDiv.style.display = 'none';
                spDiv.innerHTML = '';
                return;
            }
            const sizes = JSON.parse(opt.dataset.sizes || '{}');
            const keys = Object.keys(sizes);
            if (keys.length === 1 && keys[0] === 'All') {
                spDiv.style.display = 'none';
                spDiv.innerHTML = '';
            } else {
                let html =
                    '<select id="alacarte-size-sel" class="form-control form-control-sm mt-1"><option value="">-- Select size --</option>';
                keys.forEach(s => {
                    html +=
                        `<option value="${s}" data-price="${sizes[s]}">${s} &mdash; Rp ${sizes[s].toLocaleString('id-ID')}</option>`;
                });
                html += '</select>';
                spDiv.innerHTML = html;
                spDiv.style.display = 'block';
            }
        });

        alacarteAddBtn.addEventListener('click', function() {
            const opt = alacarteAddSel.options[alacarteAddSel.selectedIndex];
            if (!opt || !opt.value) {
                alert('Please select a service to add.');
                return;
            }
            const sizes = JSON.parse(opt.dataset.sizes || '{}');
            const keys = Object.keys(sizes);
            let size = 'All',
                price = 0;
            if (keys.length === 1 && keys[0] === 'All') {
                price = parseFloat(sizes['All']);
            } else {
                const sSel = document.getElementById('alacarte-size-sel');
                if (!sSel || !sSel.value) {
                    alert('Please select a size for ' + opt.dataset.name);
                    return;
                }
                size = sSel.value;
                price = parseInt(sSel.options[sSel.selectedIndex].dataset.price);
            }

            let bom = [];
            try {
                bom = opt.dataset.bom ? JSON.parse(opt.dataset.bom) : [];
            } catch (e) {
                console.error('Error parsing ala-carte BOM:', e);
            }

            const pkg = {
                code: opt.value,
                name: opt.dataset.name,
                size,
                price,
                bom
            };
            selectedPackages.push(pkg);
            if (bom.length > 0) appendBomItems(bom, pkg.code);
            rebuildPrimaryFields();
            renderChips();
            buildAlacarteExtras();
        });

        // ---- Main package select change ----
        function handlePackageChange() {
            sizeSelect.innerHTML = '<option value="">-- Pilih Ukuran --</option>';
            // Clear BOM items added during this session for the previously selected packages
            selectedPackages.forEach(p => removeBomItemsByPkg(p.code));
            selectedPackages = [];
            alacarteChips.innerHTML = '';
            document.getElementById('paket_code').value = '';
            document.getElementById('paket_name').value = '';
            document.getElementById('paket_size').value = '';
            document.getElementById('paket_grand_total').value = 0;
            document.getElementById('paket_price_display').style.display = 'none';
            alacarteSection.style.display = 'none';

            if (!this.value) {
                sizeField.style.display = 'none';
                return;
            }

            const option = this.options[this.selectedIndex];
            const sizes = JSON.parse(option.dataset.sizes || '{}');
            const sizeKeys = Object.keys(sizes);
            const category = option.dataset.category || '';
            let bom = [];
            try {
                bom = option.dataset.bom ? JSON.parse(option.dataset.bom) : [];
            } catch (e) {
                bom = [];
            }

            if (sizeKeys.length === 1 && sizeKeys[0] === 'All') {
                sizeField.style.display = 'none';
                const pkg = {
                    code: this.value,
                    name: option.dataset.name,
                    size: 'All',
                    price: parseFloat(sizes['All']),
                    bom
                };
                selectedPackages = [pkg];
                if (bom.length > 0) appendBomItems(bom, pkg.code);
                rebuildPrimaryFields();
            } else {
                sizeField.style.display = 'block';
                document.getElementById('paket_code').value = this.value;
                document.getElementById('paket_name').value = option.dataset.name;
                sizeKeys.forEach(size => {
                    const opt = document.createElement('option');
                    opt.value = size;
                    opt.textContent = size + ' \u2014 Rp ' + sizes[size].toLocaleString('id-ID');
                    opt.dataset.price = sizes[size];
                    sizeSelect.appendChild(opt);
                });
                selectedPackages = [{
                    code: this.value,
                    name: option.dataset.name,
                    size: '',
                    price: 0,
                    bom
                }];
            }

            if (MULTI_ADD_CATEGORIES.includes(category)) {
                alacarteSection.style.display = 'block';
                buildAlacarteExtras();
            }
        }

        function handleSizeChange() {
            if (!this.value) return;
            const price = parseInt(this.options[this.selectedIndex].dataset.price);
            if (selectedPackages.length > 0) {
                removeBomItemsByPkg(selectedPackages[0].code);
                const bom = selectedPackages[0].bom;
                selectedPackages[0].size = this.value;
                selectedPackages[0].price = price;
                if (bom.length > 0) appendBomItems(bom, selectedPackages[0].code);
            }
            rebuildPrimaryFields();
        }

        // Build item options HTML
        @php
            $itemsFormatted = $items->map(function ($item) {
                $stock = (float) $item->stocks->sum('quantity');
                $stockFormatted = $stock == floor($stock) ? number_format($stock, 0, '', '') : rtrim(rtrim(number_format($stock, 2, '.', ''), '0'), '.');
                return [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                    'stock' => $stockFormatted,
                    'smallest_uom' => $item->smallestUom,
                ];
            });
        @endphp
        const itemsData = @json($itemsFormatted);

        function buildItemOptions(selectedItemId = null) {
            const sid = (selectedItemId !== null && selectedItemId !== undefined) ? String(selectedItemId) : '';
            let html = '<option value="">Select Item (Optional)</option>';
            itemsData.forEach(item => {
                const isSelected = sid !== '' && String(item.id) === sid;
                html +=
                    `<option value="${item.id}" data-stock="${item.stock}" data-uom="${item.smallest_uom?.code || '-'}" data-code="${item.code}" ${isSelected ? 'selected' : ''}>[${item.code}] ${item.name}</option>`;
            });
            return html;
        }

        // Build BOM row
        function buildBomRow(bi, idx, pkgCode) {
            const div = document.createElement('div');
            div.className = 'item-row card mb-2 border-left-primary';
            div.dataset.pkg = pkgCode || '';
            div.innerHTML = `
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <div class="form-group mb-0">
                                <label><strong>Item</strong></label>
                                <select name="items[${idx}][item_id]" class="form-control item-select">
                                    ${buildItemOptions(bi.item_id)}
                                </select>
                                <small class="form-text text-muted item-stock">Stock: ${bi.stock} ${bi.uom_code}</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group mb-0">
                                <label><strong>Demand Qty</strong></label>
                                <div class="input-group">
                                    <input type="number" name="items[${idx}][demand_quantity]" class="form-control qty" step="0.01" min="0.01" value="${bi.quantity}">
                                    <div class="input-group-append"><span class="input-group-text uom-display">${bi.uom_code}</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-0">
                                <label><strong>Remark</strong></label>
                                <input type="text" name="items[${idx}][remark]" class="form-control" placeholder="e.g., Bundling HRM, WIP 39780">
                            </div>
                        </div>
                        <div class="col-md-2 mt-3">
                            <button type="button" class="btn btn-danger btn-sm remove-item"><i class="fas fa-trash"></i> Remove</button>
                        </div>
                    </div>
                </div>`;
            return div;
        }

        // Append BOM items, tagged with pkgCode so they can be removed later
        // if that package is swapped out or removed from the selection.
        function appendBomItems(bom, pkgCode) {
            const container = document.getElementById('items-container');
            const fragment = document.createDocumentFragment();
            bom.forEach(bi => {
                fragment.appendChild(buildBomRow(bi, itemIndex, pkgCode));
                itemIndex++;
            });
            container.appendChild(fragment);
            attachItemListeners();
            initItemSelect2();
        }

        // Remove BOM rows that were added (during this session) for the given pkgCode.
        // Pre-existing rows loaded from the database have no pkg tag and are left alone.
        function removeBomItemsByPkg(pkgCode) {
            if (!pkgCode) return;
            document.querySelectorAll(`.item-row[data-pkg="${pkgCode}"]`).forEach(r => r.remove());
            renumberItemRows();
        }

        function renumberItemRows() {
            document.querySelectorAll('.item-row').forEach((row, i) => {
                row.querySelectorAll('[name^="items["]').forEach(el => {
                    el.name = el.name.replace(/^items\[\d+\]/, `items[${i}]`);
                });
            });
            itemIndex = document.querySelectorAll('.item-row').length;
        }

        // ---- Reconstruct the multi-package selection from the WO's saved data ----
        // paket_code / paket_name / paket_size are " + "/", "-joined strings; rebuild
        // selectedPackages by looking up each code's current price/BOM definition.
        (function initExistingPackages() {
            const initCodes = (document.getElementById('paket_code').value || '').split(' + ').filter(Boolean);
            const initNames = (document.getElementById('paket_name').value || '').split(' + ').filter(Boolean);
            const rawSize = document.getElementById('paket_size').value || '';
            const initSizes = rawSize ? rawSize.split(', ') : [];
            if (!initCodes.length) return;

            initCodes.forEach((code, i) => {
                const opt = paketSelect.querySelector(`option[value="${code}"]`);
                const size = initSizes[i] || 'All';
                let name = initNames[i] || code,
                    price = 0,
                    bom = [];
                if (opt) {
                    name = opt.dataset.name || name;
                    const sizes = JSON.parse(opt.dataset.sizes || '{}');
                    price = sizes[size] !== undefined ? parseFloat(sizes[size]) : 0;
                    try {
                        bom = opt.dataset.bom ? JSON.parse(opt.dataset.bom) : [];
                    } catch (e) {
                        bom = [];
                    }
                }
                selectedPackages.push({
                    code,
                    name,
                    size,
                    price,
                    bom
                });
            });

            const main = selectedPackages[0];
            const mainOpt = paketSelect.querySelector(`option[value="${main.code}"]`);
            if (mainOpt) {
                paketSelect.value = main.code;
                const sizes = JSON.parse(mainOpt.dataset.sizes || '{}');
                const sizeKeys = Object.keys(sizes);
                if (!(sizeKeys.length === 1 && sizeKeys[0] === 'All')) {
                    sizeField.style.display = 'block';
                    sizeKeys.forEach(s => {
                        const o = document.createElement('option');
                        o.value = s;
                        o.textContent = s + ' \u2014 Rp ' + sizes[s].toLocaleString('id-ID');
                        o.dataset.price = sizes[s];
                        if (s === main.size) o.selected = true;
                        sizeSelect.appendChild(o);
                    });
                }
                const category = mainOpt.dataset.category || '';
                if (MULTI_ADD_CATEGORIES.includes(category)) {
                    alacarteSection.style.display = 'block';
                    buildAlacarteExtras();
                }
            }

            renderChips();
            const grandTotal = parseFloat(document.getElementById('paket_grand_total').value) || 0;
            if (grandTotal > 0) updatePriceDisplay(grandTotal);
        })();

        paketSelect.addEventListener('change', handlePackageChange);
        sizeSelect.addEventListener('change', handleSizeChange);

        function updatePriceDisplay(grandTotal) {
            const material = grandTotal - LABOR_FIXED;
            document.getElementById('display_material').textContent = 'Rp ' + material.toLocaleString('id-ID');
            document.getElementById('display_grand_total').textContent = 'Rp ' + (grandTotal + getAddonLaborTotal())
                .toLocaleString('id-ID');
            document.getElementById('paket_price_display').style.display = 'block';
        }

        function getAddonLaborTotal() {
            let total = 0;
            document.querySelectorAll('.labor-total-price').forEach(function(el) {
                total += parseFloat(el.value) || 0;
            });
            return total;
        }

        function refreshGrandTotal() {
            const paketTotal = parseFloat(document.getElementById('paket_grand_total').value) || 0;
            if (!paketTotal) return;
            const addonTotal = getAddonLaborTotal();
            const material = paketTotal - LABOR_FIXED;
            document.getElementById('display_material').textContent = 'Rp ' + material.toLocaleString('id-ID');
            const addonRow = document.getElementById('display_addon_row');
            if (addonTotal > 0) {
                document.getElementById('display_addon_labor').textContent = 'Rp ' + addonTotal.toLocaleString('id-ID');
                addonRow.style.display = '';
            } else {
                addonRow.style.display = 'none';
            }
            document.getElementById('display_grand_total').textContent = 'Rp ' + (paketTotal + addonTotal).toLocaleString(
                'id-ID');
            document.getElementById('paket_price_display').style.display = 'block';
        }

        // Stub - will be overridden once jQuery/Select2 are loaded
        function initItemSelect2() {}

        document.getElementById('add-item').addEventListener('click', function() {
            const container = document.getElementById('items-container');
            const itemOptions = buildItemOptions();

            const newItemRow = document.createElement('div');
            newItemRow.className = 'item-row card mb-2 border-left-primary';
            newItemRow.innerHTML = `
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <div class="form-group mb-0">
                                <label><strong>Item</strong></label>
                                <select name="items[${itemIndex}][item_id]" class="form-control item-select">
                                    ${itemOptions}
                                </select>
                                <small class="form-text text-muted item-stock"></small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group mb-0">
                                <label><strong>Qty</strong></label>
                                <div class="input-group">
                                    <input type="number" name="items[${itemIndex}][demand_quantity]" class="form-control qty" step="0.01" min="0.01" placeholder="0.00">
                                    <div class="input-group-append"><span class="input-group-text uom-display">-</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-0">
                                <label><strong>Remark</strong></label>
                                <input type="text" name="items[${itemIndex}][remark]" class="form-control" placeholder="e.g., Bundling HRM, WIP 39780">
                            </div>
                        </div>
                        <div class="col-md-2 mt-3">
                            <button type="button" class="btn btn-danger btn-sm remove-item"><i class="fas fa-trash"></i> Remove</button>
                        </div>
                    </div>
                </div>`;
            container.appendChild(newItemRow);
            itemIndex++;
            attachItemListeners();
            initItemSelect2();
        });

        document.getElementById('add-labor').addEventListener('click', function() {
            const container = document.getElementById('labors-container');
            const newLaborRow = document.createElement('div');
            newLaborRow.className = 'labor-row card mb-2 border-left-success';
            newLaborRow.innerHTML = `
                <div class="card-body py-2">
                    <div class="row align-items-end">
                        <div class="col-md-6">
                            <div class="form-group mb-0">
                                <label><strong>Labor Description</strong></label>
                                <input type="text" name="labors[${laborIndex}][description]" class="form-control labor-description" placeholder="Enter labor description">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group mb-0">
                                <label><strong>Qty</strong></label>
                                <input type="number" name="labors[${laborIndex}][qty]" class="form-control labor-qty" step="1" min="1" value="1">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-0">
                                <label><strong>Remarks</strong></label>
                                <input type="text" name="labors[${laborIndex}][remarks]" class="form-control" placeholder="Optional notes">
                            </div>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-danger btn-sm remove-labor"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>`;
            container.appendChild(newLaborRow);
            laborIndex++;
            attachLaborListeners();
        });

        // ===== LABOR FUNCTIONS =====
        function attachLaborListeners() {}

        attachLaborListeners();

        function attachItemListeners() {
            document.querySelectorAll('.item-select').forEach(select => {
                select.onchange = function() {
                    const row = this.closest('.item-row');
                    const opt = this.options[this.selectedIndex];
                    row.querySelector('.item-stock').textContent = opt.value ?
                        `Stock: ${opt.dataset.stock} ${opt.dataset.uom}` : '';
                    row.querySelector('.uom-display').textContent = opt.dataset.uom || '-';
                };
            });
        }

        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-item')) {
                if (document.querySelectorAll('.item-row').length > 1) {
                    e.target.closest('.item-row').remove();
                    renumberItemRows();
                }
            }
            if (e.target.closest('.remove-labor')) {
                if (document.querySelectorAll('.labor-row').length > 1) e.target.closest('.labor-row').remove();
            }
        });

        attachItemListeners();

        // ===== STRIP BLANK ROWS BEFORE SUBMIT =====
        document.querySelector('form').addEventListener('submit', function() {
            document.querySelectorAll('.labor-row').forEach(function(row) {
                const desc = row.querySelector('.labor-description');
                if (!desc || !desc.value.trim()) row.remove();
            });
            document.querySelectorAll('.item-row').forEach(function(row) {
                const sel = row.querySelector('select[name*="[item_id]"]');
                if (!sel || !sel.value) row.remove();
            });
        });

        // ===== REFERENCE WO TOGGLE (INT_W3) =====
        // Moved to scripts section to work with Select2
    </script>
@endsection

@section('scripts')
    <script>
        // ===== REFERENCE WO TOGGLE (INT_W3) =====
        function toggleRefWo() {
            const accountCode = $('#account_code').val();
            if (accountCode === 'INT_W3') {
                $('#reference_wo_group').show();
            } else {
                $('#reference_wo_group').hide();
                $('#reference_wo_id').val('');
            }
        }

        $('#account_code').on('change', function() {
            console.log('[ACCOUNT] Account code changed:', this.value);
            toggleRefWo();
        });

        function initItemSelect2() {
            $('.item-select').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    const savedVal = $(this).val();
                    $(this).select2({
                        placeholder: 'Select Item (Optional)',
                        allowClear: true,
                        theme: 'bootstrap4',
                        width: '100%'
                    }).on('change', function() {
                        const row = this.closest('.item-row');
                        const opt = this.options[this.selectedIndex];
                        row.querySelector('.item-stock').textContent = opt.value ?
                            'Stock: ' + (opt.dataset.stock || '0') + ' ' + (opt.dataset.uom || '') : '';
                        row.querySelector('.uom-display').textContent = opt.dataset.uom || '-';
                    });
                    if (savedVal) {
                        $(this).val(savedVal).trigger('change');
                    }
                }
            });
        }

        // Run INSIDE $(document).ready so it fires AFTER the layout's global
        // $('.select2').select2({...}) init — which registers its ready handler first.
        // If initItemSelect2() runs before the layout's ready handler, the layout
        // would re-wrap Select2's own .select2 container spans, corrupting the display.
        $(document).ready(function() {
            toggleRefWo();

            $('#reference_wo_id').select2({
                placeholder: '-- Select Reference WO --',
                allowClear: true,
                theme: 'bootstrap4'
            });

            initItemSelect2();
            initLaborSelect2();
        });
    </script>
@endsection
