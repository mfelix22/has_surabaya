@extends('layouts.admin')

@section('title', 'COGS by Package')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-chart-pie mr-2"></i>COGS by Package (Coating / Ala-Carte)</h1>
    </div>
@endsection

@section('content')
    <div class="container-fluid">

        {{-- ===== FILTER CARD ===== --}}
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filters</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <form method="GET" action="{{ route('reports.package_cogs') }}" id="filterForm">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Date From</label>
                                <input type="date" name="date_from" class="form-control form-control-sm"
                                    value="{{ request('date_from') }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Date To</label>
                                <input type="date" name="date_to" class="form-control form-control-sm"
                                    value="{{ request('date_to') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category" class="form-control form-control-sm select2">
                                    <option value="">-- All Categories --</option>
                                    <option value="coating" {{ request('category') === 'coating' ? 'selected' : '' }}>Coating</option>
                                    <option value="ala_carte" {{ request('category') === 'ala_carte' ? 'selected' : '' }}>Ala-Carte</option>
                                    <option value="no_package" {{ request('category') === 'no_package' ? 'selected' : '' }}>No Package</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label>Package</label>
                                <select name="package_codes[]" class="form-control form-control-sm select2" multiple="multiple" data-placeholder="-- All Packages --">
                                    @foreach ($packages as $pkg)
                                        <option value="{{ $pkg->code }}"
                                            {{ in_array($pkg->code, (array) request('package_codes', [])) ? 'selected' : '' }}>
                                            [{{ $pkg->code }}] {{ $pkg->name }} ({{ $pkg->category }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Work Order #</label>
                                <input type="text" name="wo_number" class="form-control form-control-sm"
                                    value="{{ request('wo_number') }}" placeholder="e.g. 2607/HAS/025">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-search mr-1"></i>Apply Filter
                    </button>
                    <a href="{{ route('reports.package_cogs') }}" class="btn btn-secondary btn-sm ml-1">
                        <i class="fas fa-times mr-1"></i>Clear Filter
                    </a>
                </div>
            </form>
        </div>

        {{-- ===== SUMMARY CARDS ===== --}}
        <div class="row">
            <div class="col-md-4">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-info"><i class="fas fa-list-ol"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Transactions</span>
                        <span class="info-box-number">{{ number_format($totalTransactions) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-success"><i class="fas fa-boxes"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Unique Parts Used</span>
                        <span class="info-box-number">{{ number_format($uniqueParts) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-warning"><i class="fas fa-money-bill-wave"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total COGS</span>
                        <span class="info-box-number">Rp {{ number_format($totalCost, 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== TABS ===== --}}
        <div class="card card-outline card-info">
            <div class="card-header p-0 pt-1">
                <ul class="nav nav-tabs" id="reportTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-category" data-toggle="tab" href="#pane-category" role="tab">
                            <i class="fas fa-puzzle-piece mr-1"></i>Summary by Category
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-package" data-toggle="tab" href="#pane-package" role="tab">
                            <i class="fas fa-box mr-1"></i>Summary by Package
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-item-type" data-toggle="tab" href="#pane-item-type" role="tab">
                            <i class="fas fa-layer-group mr-1"></i>Summary by Item Type
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-item-detail" data-toggle="tab" href="#pane-item-detail" role="tab">
                            <i class="fas fa-list-alt mr-1"></i>Detailed by Item
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-work-order-items" data-toggle="tab" href="#pane-work-order-items" role="tab">
                            <i class="fas fa-boxes mr-1"></i>Summary by Work Order (Items)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-detail" data-toggle="tab" href="#pane-detail" role="tab">
                            <i class="fas fa-table mr-1"></i>Detailed Transactions
                        </a>
                    </li>
                    <li class="nav-item ml-auto d-flex align-items-center pr-2">
                        <button class="btn btn-success btn-sm" id="exportBtn">
                            <i class="fas fa-file-excel mr-1"></i>Export to Excel
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body tab-content">

                {{-- ---- Tab 1: By Category ---- --}}
                <div class="tab-pane fade show active" id="pane-category" role="tabpanel">
                    <table id="tbl-category" class="table table-bordered table-striped table-sm dataTable" style="width:100%">
                        <thead>
                            <tr class="bg-info">
                                <th>No</th>
                                <th>Category</th>
                                <th>Total Qty Used</th>
                                <th>Usage Count</th>
                                <th>Total COGS (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($byCategory as $row)
                                <tr>
                                    <td></td>
                                    <td><span class="badge badge-primary">{{ $row->package_group }}</span></td>
                                    <td class="text-right">{{ number_format((float) $row->total_qty, 2) }}</td>
                                    <td class="text-right">{{ number_format((int) $row->usage_count) }}</td>
                                    <td class="text-right font-weight-bold">
                                        {{ number_format((float) $row->total_cost, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No data found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($byCategory->count())
                            <tfoot>
                                <tr class="font-weight-bold bg-light">
                                    <td colspan="2" class="text-right">Total</td>
                                    <td class="text-right">{{ number_format($byCategory->sum('total_qty'), 2) }}</td>
                                    <td class="text-right">{{ number_format($byCategory->sum('usage_count')) }}</td>
                                    <td class="text-right">{{ number_format($byCategory->sum('total_cost'), 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                {{-- ---- Tab 2: By Package ---- --}}
                <div class="tab-pane fade" id="pane-package" role="tabpanel">
                    <table id="tbl-package" class="table table-bordered table-striped table-sm dataTable" style="width:100%">
                        <thead>
                            <tr class="bg-info">
                                <th>No</th>
                                <th>Group</th>
                                <th>Package Code</th>
                                <th>Package Name</th>
                                <th>Total Qty Used</th>
                                <th>Usage Count</th>
                                <th>Total COGS (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($byPackage as $row)
                                <tr>
                                    <td></td>
                                    <td><span class="badge badge-primary">{{ $row->package_group }}</span></td>
                                    <td><code>{{ $row->paket_code }}</code></td>
                                    <td>{{ $row->paket_name }}</td>
                                    <td class="text-right">{{ number_format((float) $row->total_qty, 2) }}</td>
                                    <td class="text-right">{{ number_format((int) $row->usage_count) }}</td>
                                    <td class="text-right font-weight-bold">
                                        {{ number_format((float) $row->total_cost, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No data found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($byPackage->count())
                            <tfoot>
                                <tr class="font-weight-bold bg-light">
                                    <td colspan="4" class="text-right">Total</td>
                                    <td class="text-right">{{ number_format($byPackage->sum('total_qty'), 2) }}</td>
                                    <td class="text-right">{{ number_format($byPackage->sum('usage_count')) }}</td>
                                    <td class="text-right">{{ number_format($byPackage->sum('total_cost'), 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                {{-- ---- Tab 3: By Category & Item Type ---- --}}
                <div class="tab-pane fade" id="pane-item-type" role="tabpanel">
                    <table id="tbl-item-type" class="table table-bordered table-striped table-sm dataTable" style="width:100%">
                        <thead>
                            <tr class="bg-info">
                                <th>No</th>
                                <th>Package Category</th>
                                <th>Item Type</th>
                                <th>Item Type Name</th>
                                <th>Total Qty Used</th>
                                <th>Usage Count</th>
                                <th>Total COGS (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($byItemType as $row)
                                <tr>
                                    <td></td>
                                    <td><span class="badge badge-primary">{{ $row->package_group }}</span></td>
                                    <td class="text-center"><code>{{ $row->item_type }}</code></td>
                                    <td>{{ $row->item_type_name }}</td>
                                    <td class="text-right">{{ number_format((float) $row->total_qty, 2) }}</td>
                                    <td class="text-right">{{ number_format((int) $row->usage_count) }}</td>
                                    <td class="text-right font-weight-bold">
                                        {{ number_format((float) $row->total_cost, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No data found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($byItemType->count())
                            <tfoot>
                                <tr class="font-weight-bold bg-light">
                                    <td colspan="4" class="text-right">Total</td>
                                    <td class="text-right">{{ number_format($byItemType->sum('total_qty'), 2) }}</td>
                                    <td class="text-right">{{ number_format($byItemType->sum('usage_count')) }}</td>
                                    <td class="text-right">{{ number_format($byItemType->sum('total_cost'), 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                {{-- ---- Tab 4: Detailed by Item ---- --}}
                <div class="tab-pane fade" id="pane-item-detail" role="tabpanel">
                    <table id="tbl-item-detail" class="table table-bordered table-striped table-sm dataTable" style="width:100%">
                        <thead>
                            <tr class="bg-info">
                                <th>No</th>
                                <th>Package Category</th>
                                <th>Item Type</th>
                                <th>Item Type Name</th>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th>Total Qty Used</th>
                                <th>Usage Count</th>
                                <th>Avg Price (Rp)</th>
                                <th>Total COGS (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($byItemDetail as $row)
                                <tr>
                                    <td></td>
                                    <td><span class="badge badge-primary">{{ $row->package_group }}</span></td>
                                    <td class="text-center"><code>{{ $row->item_type }}</code></td>
                                    <td>{{ $row->item_type_name }}</td>
                                    <td>
                                        <a href="{{ route('items.show', $row->item_id) }}" target="_blank">
                                            <code>{{ $row->item_code }}</code>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ route('items.show', $row->item_id) }}" target="_blank">
                                            {{ $row->item_name }}
                                        </a>
                                    </td>
                                    <td class="text-right">{{ number_format((float) $row->total_qty, 2) }}</td>
                                    <td class="text-right">{{ number_format((int) $row->usage_count) }}</td>
                                    <td class="text-right">{{ number_format((float) $row->avg_price, 2, ',', '.') }}</td>
                                    <td class="text-right font-weight-bold">
                                        {{ number_format((float) $row->total_cost, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted">No data found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($byItemDetail->count())
                            <tfoot>
                                <tr class="font-weight-bold bg-light">
                                    <td colspan="6" class="text-right">Total</td>
                                    <td class="text-right">{{ number_format($byItemDetail->sum('total_qty'), 2) }}</td>
                                    <td class="text-right">{{ number_format($byItemDetail->sum('usage_count')) }}</td>
                                    <td></td>
                                    <td class="text-right">{{ number_format($byItemDetail->sum('total_cost'), 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                {{-- ---- Tab 5: Summary by Work Order & Item ---- --}}
                <div class="tab-pane fade" id="pane-work-order-items" role="tabpanel">
                    <table id="tbl-work-order-items" class="table table-bordered table-striped table-sm dataTable" style="width:100%">
                        <thead>
                            <tr class="bg-info">
                                <th>No</th>
                                <th>WO #</th>
                                <th>Vehicle</th>
                                <th>Merk</th>
                                <th>Package</th>
                                <th>Package Group</th>
                                <th>Item Type</th>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th>Qty Used</th>
                                <th>Avg Price (Rp)</th>
                                <th>Total COGS (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($byWorkOrderItems as $row)
                                <tr>
                                    <td></td>
                                    <td>
                                        <a href="{{ route('work_orders.show', $row->work_order_id) }}" target="_blank">
                                            <strong>{{ $row->wo_number }}</strong>
                                        </a>
                                    </td>
                                    <td><strong>{{ $row->vehicle_plate }}</strong></td>
                                    <td>{{ $row->vehicle_merk }}</td>
                                    <td>{{ $row->paket_name }}</td>
                                    <td><span class="badge badge-primary">{{ $row->package_group }}</span></td>
                                    <td class="text-center"><code>{{ $row->item_type }}</code> - {{ $row->item_type_name }}</td>
                                    <td>
                                        <a href="{{ route('items.show', $row->item_id) }}" target="_blank">
                                            <code>{{ $row->item_code }}</code>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ route('items.show', $row->item_id) }}" target="_blank">
                                            {{ $row->item_name }}
                                        </a>
                                    </td>
                                    <td class="text-right">{{ number_format((float) $row->total_qty, 2) }}</td>
                                    <td class="text-right">{{ number_format((float) $row->avg_price, 2, ',', '.') }}</td>
                                    <td class="text-right font-weight-bold">
                                        {{ number_format((float) $row->total_cost, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center text-muted">No data found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($byWorkOrderItems->count())
                            <tfoot>
                                <tr class="font-weight-bold bg-light">
                                    <td colspan="9" class="text-right">Total</td>
                                    <td class="text-right">{{ number_format($byWorkOrderItems->sum('total_qty'), 2) }}</td>
                                    <td></td>
                                    <td class="text-right">{{ number_format($byWorkOrderItems->sum('total_cost'), 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                {{-- ---- Tab 6: Detailed ---- --}}
                <div class="tab-pane fade" id="pane-detail" role="tabpanel">
                    <table id="tbl-detail" class="table table-bordered table-striped table-sm dataTable" style="width:100%">
                        <thead>
                            <tr class="bg-info">
                                <th>No</th>
                                <th>Date</th>
                                <th>Bon Out #</th>
                                <th>WO #</th>
                                <th>Vehicle</th>
                                <th>Merk</th>
                                <th>Group</th>
                                <th>Package Code</th>
                                <th>Package Name</th>
                                <th>Part Code</th>
                                <th>Part Name</th>
                                <th>Qty</th>
                                <th>Unit Cost</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($detailed as $row)
                                <tr>
                                    <td></td>
                                    <td>{{ \Carbon\Carbon::parse($row->issued_date)->format('d/m/Y') }}</td>
                                    <td>
                                        <a href="{{ route('bon_outs.show', $row->bon_out_id) }}" target="_blank">
                                            {{ $row->bon_out_number }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ route('work_orders.show', $row->work_order_id) }}" target="_blank">
                                            {{ $row->wo_number }}
                                        </a>
                                    </td>
                                    <td><strong>{{ $row->vehicle_plate }}</strong></td>
                                    <td>{{ $row->vehicle_merk }}</td>
                                    <td><span class="badge badge-primary">{{ $row->package_group }}</span></td>
                                    <td><code>{{ $row->paket_code }}</code></td>
                                    <td>{{ $row->paket_name }}</td>
                                    <td>
                                        <a href="{{ route('items.show', $row->item_id) }}" target="_blank">
                                            <code>{{ $row->item_code }}</code>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ route('items.show', $row->item_id) }}" target="_blank">
                                            {{ $row->item_name }}
                                        </a>
                                    </td>
                                    <td class="text-right">{{ number_format((float) $row->actual_quantity, 2) }}</td>
                                    <td class="text-right">{{ number_format((float) $row->unit_cost, 2, ',', '.') }}</td>
                                    <td class="text-right font-weight-bold">
                                        {{ number_format((float) $row->line_cost, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="14" class="text-center text-muted">No data found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($detailed->count())
                            <tfoot>
                                <tr class="font-weight-bold bg-light">
                                    <td colspan="11" class="text-right">Total</td>
                                    <td class="text-right">{{ number_format($detailed->sum('actual_quantity'), 2) }}</td>
                                    <td></td>
                                    <td class="text-right">{{ number_format($detailed->sum('line_cost'), 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

            </div>{{-- card-body --}}
        </div>{{-- card --}}

    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            function rowNumberCallback() {
                var api = this.api();
                var start = api.page.info().start;
                api.column(0, {
                    page: 'current'
                }).nodes().each(function(cell, i) {
                    cell.innerHTML = start + i + 1;
                });
            }

            $('#tbl-category').DataTable({
                pageLength: 25,
                order: [[4, 'desc']],
                columnDefs: [{ orderable: false, targets: 0 }],
                drawCallback: rowNumberCallback
            });
            $('#tbl-package').DataTable({
                pageLength: 25,
                order: [[6, 'desc']],
                columnDefs: [{ orderable: false, targets: 0 }],
                drawCallback: rowNumberCallback
            });
            $('#tbl-item-type').DataTable({
                pageLength: 25,
                order: [[6, 'desc']],
                columnDefs: [{ orderable: false, targets: 0 }],
                drawCallback: rowNumberCallback
            });
            $('#tbl-item-detail').DataTable({
                pageLength: 25,
                order: [[9, 'desc']],
                columnDefs: [{ orderable: false, targets: 0 }],
                drawCallback: rowNumberCallback
            });
            $('#tbl-work-order-items').DataTable({
                pageLength: 25,
                order: [[11, 'desc']],
                columnDefs: [{ orderable: false, targets: 0 }],
                drawCallback: rowNumberCallback
            });
            $('#tbl-detail').DataTable({
                pageLength: 25,
                order: [[1, 'desc']],
                columnDefs: [{ orderable: false, targets: 0 }],
                drawCallback: rowNumberCallback
            });

            $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
            });

            $('.select2').select2({
                theme: 'bootstrap4',
                width: '100%'
            });

            var activeTab = 'by_category';
            $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                var target = $(e.target).attr('href');
                if (target === '#pane-category') activeTab = 'by_category';
                if (target === '#pane-package') activeTab = 'by_package';
                if (target === '#pane-item-type') activeTab = 'by_item_type';
                if (target === '#pane-item-detail') activeTab = 'by_item_detail';
                if (target === '#pane-work-order-items') activeTab = 'by_work_order_items';
                if (target === '#pane-detail') activeTab = 'by_detail';
            });

            $('#exportBtn').on('click', function() {
                var params = $('#filterForm').serialize();
                params += '&tab=' + activeTab;
                window.location.href = '{{ route('reports.package_cogs.export') }}?' + params;
            });
        });
    </script>
@endpush
