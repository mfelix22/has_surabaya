<?php

namespace App\Http\Controllers;

use App\Models\VendorComparison;
use App\Models\VendorComparisonVendor;
use App\Models\VendorComparisonItem;
use App\Models\VendorComparisonVendorPrice;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class VendorComparisonController extends Controller
{
    private function generateComparisonNumber(): string
    {
        $last = VendorComparison::orderBy('id', 'desc')->first();
        $seq = $last ? (int) substr($last->comparison_number, -3) + 1 : 1;
        return 'FK-PCH-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    public function index()
    {
        $comparisons = VendorComparison::with(['creator', 'selectedVendor'])
            ->withCount('vendors')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('vendor_comparisons.index', compact('comparisons'));
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['purchasing', 'admin', 'super_admin'])) {
            return redirect()->route('vendor_comparisons.index')
                ->with('error', 'Only purchasing staff can create Vendor Comparison Forms.');
        }

        $prs = PurchaseRequest::whereIn('status', ['on_progress', 'completed', 'printed'])
            ->whereDoesntHave('details', function ($q) {
                $q->where('ordered_quantity', '>', 0);
            })
            ->with(['details.item', 'details.uom'])
            ->orderBy('created_at', 'desc')
            ->get();

        $prsJson = $prs->mapWithKeys(function ($pr) {
            return [$pr->id => $pr->details->map(function ($d) {
                return [
                    'id'   => $d->id,
                    'name' => $d->is_custom_item
                        ? ($d->custom_item_name ?: $d->service_description ?: 'Custom Item')
                        : ($d->item ? $d->item->name : 'Unknown Item'),
                    'code' => $d->is_custom_item ? '' : ($d->item ? $d->item->code : ''),
                    'qty'  => (float) $d->quantity,
                    'uom'  => $d->uom ? $d->uom->name : '',
                ];
            })->values()];
        });

        $suppliers = Supplier::orderBy('name')->get();
        $selectedPrId = $request->query('pr_id');

        return view('vendor_comparisons.create', compact('prs', 'suppliers', 'selectedPrId', 'prsJson'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['purchasing', 'admin', 'super_admin'])) {
            return redirect()->route('vendor_comparisons.index')
                ->with('error', 'Only purchasing staff can create Vendor Comparison Forms.');
        }

        $request->validate($this->vendorComparisonRules());

        $comparison = null;

        DB::transaction(function () use ($request, $user, &$comparison) {
            $comparison = VendorComparison::create([
                'comparison_number'   => $this->generateComparisonNumber(),
                'purchase_request_id' => $request->purchase_request_id,
                'tanggal'             => $request->tanggal,
                'detail_barang_jasa'  => $request->detail_barang_jasa,
                'notes'               => $request->notes,
                'status'              => 'draft',
                'created_by'          => $user->id,
            ]);

            $this->saveItemsAndVendors($request, $comparison);
        });

        return redirect()->route('vendor_comparisons.show', $comparison)
            ->with('success', 'Vendor Comparison Form created successfully.');
    }

    /**
     * Validation rules shared by store() and update().
     */
    private function vendorComparisonRules(): array
    {
        return [
            'tanggal'             => 'required|date',
            'detail_barang_jasa'  => 'required|string',
            'purchase_request_id' => 'nullable|exists:purchase_requests,id',
            'notes'               => 'nullable|string',
            'items'               => 'required|array|min:1',
            'items.*.name'        => 'required|string|max:255',
            'items.*.qty'         => 'nullable|numeric|min:0',
            'items.*.uom'         => 'nullable|string|max:50',
            'items.*.pr_detail_id' => 'nullable|integer|exists:purchase_request_details,id',
            'vendors'             => 'required|array|min:1|max:3',
            'vendors.*.nama_calon_vendor' => 'required|string|max:200',
            'vendors.*.alamat'            => 'nullable|string',
            'vendors.*.telepon_fax'       => 'nullable|string|max:100',
            'vendors.*.email'             => 'nullable|email|max:200',
            'vendors.*.pic_contact_person' => 'nullable|string|max:200',
            'vendors.*.metode_pembayaran' => 'nullable|string|max:100',
            'vendors.*.rekening_bank'     => 'nullable|string|max:255',
            'vendors.*.term_of_payment'   => 'nullable|string|max:100',
            'vendors.*.harga_barang_jasa' => 'nullable|numeric|min:0',
            'vendors.*.prices'            => 'nullable|array',
            'vendors.*.prices.*'          => 'nullable|numeric|min:0',
            'vendors.*.include_ppn'       => 'nullable|boolean',
            'vendors.*.ketentuan_lain'    => 'nullable|string',
        ];
    }

    /**
     * Create the comparison items and vendors (with per-item prices) for the given comparison.
     * Assumes any previous items/vendors have already been removed (for updates).
     */
    private function saveItemsAndVendors(Request $request, VendorComparison $comparison): void
    {
        $itemIdMap = [];
        foreach ($request->input('items', []) as $key => $itemData) {
            $item = VendorComparisonItem::create([
                'vendor_comparison_id' => $comparison->id,
                'pr_detail_id'         => $itemData['pr_detail_id'] ?? null,
                'item_name'            => $itemData['name'],
                'qty'                  => $itemData['qty'] ?? null,
                'uom'                  => $itemData['uom'] ?? null,
                'sort_order'           => count($itemIdMap),
            ]);
            $itemIdMap[$key] = $item->id;
        }

        foreach (array_values($request->vendors) as $index => $vendorData) {
            $vendor = VendorComparisonVendor::create([
                'vendor_comparison_id' => $comparison->id,
                'vendor_order'         => $index + 1,
                'nama_calon_vendor'    => $vendorData['nama_calon_vendor'],
                'alamat'               => $vendorData['alamat'] ?? null,
                'telepon_fax'          => $vendorData['telepon_fax'] ?? null,
                'email'                => $vendorData['email'] ?? null,
                'pic_contact_person'   => $vendorData['pic_contact_person'] ?? null,
                'metode_pembayaran'    => $vendorData['metode_pembayaran'] ?? null,
                'rekening_bank'        => $vendorData['rekening_bank'] ?? null,
                'term_of_payment'      => $vendorData['term_of_payment'] ?? null,
                'harga_barang_jasa'    => null,
                'include_ppn'          => !empty($vendorData['include_ppn']),
                'ketentuan_lain'       => $vendorData['ketentuan_lain'] ?? null,
            ]);

            $total = 0;
            foreach (($vendorData['prices'] ?? []) as $itemKey => $harga) {
                if ($harga === null || $harga === '' || !isset($itemIdMap[$itemKey])) {
                    continue;
                }
                VendorComparisonVendorPrice::create([
                    'vendor_comparison_vendor_id' => $vendor->id,
                    'vendor_comparison_item_id'   => $itemIdMap[$itemKey],
                    'harga'                       => $harga,
                ]);
                $total += (float) $harga;
            }

            if ($total > 0) {
                $vendor->update(['harga_barang_jasa' => $total]);
            } elseif (!empty($vendorData['harga_barang_jasa'])) {
                // Fallback for a manually entered total when no per-item prices were provided.
                $vendor->update(['harga_barang_jasa' => $vendorData['harga_barang_jasa']]);
            }
        }
    }

    public function show(VendorComparison $vendorComparison)
    {
        $vendorComparison->load(['vendors.prices', 'items', 'creator', 'approver', 'purchaseRequest', 'selectedVendor']);
        return view('vendor_comparisons.show', compact('vendorComparison'));
    }

    public function edit(VendorComparison $vendorComparison)
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['purchasing', 'admin', 'super_admin'])) {
            return redirect()->route('vendor_comparisons.show', $vendorComparison)
                ->with('error', 'Only purchasing staff can edit Vendor Comparison Forms.');
        }

        if ($vendorComparison->status !== 'draft') {
            return redirect()->route('vendor_comparisons.show', $vendorComparison)
                ->with('error', 'Only draft forms can be edited.');
        }

        $vendorComparison->load(['vendors.prices', 'items', 'purchaseRequest']);

        $currentPrId = $vendorComparison->purchase_request_id;

        $prs = PurchaseRequest::whereIn('status', ['on_progress', 'completed', 'printed'])
            ->where(function ($q) use ($currentPrId) {
                $q->whereDoesntHave('details', function ($q2) {
                      $q2->where('ordered_quantity', '>', 0);
                  })
                  ->orWhere('id', $currentPrId);
            })
            ->with(['details.item', 'details.uom'])
            ->orderBy('created_at', 'desc')
            ->get();

        $prsJson = $prs->mapWithKeys(function ($pr) {
            return [$pr->id => $pr->details->map(function ($d) {
                return [
                    'id'   => $d->id,
                    'name' => $d->is_custom_item
                        ? ($d->custom_item_name ?: $d->service_description ?: 'Custom Item')
                        : ($d->item ? $d->item->name : 'Unknown Item'),
                    'code' => $d->is_custom_item ? '' : ($d->item ? $d->item->code : ''),
                    'qty'  => (float) $d->quantity,
                    'uom'  => $d->uom ? $d->uom->name : '',
                ];
            })->values()];
        });

        $suppliers = Supplier::orderBy('name')->get();

        return view('vendor_comparisons.edit', compact('vendorComparison', 'prs', 'suppliers', 'prsJson'));
    }

    public function update(Request $request, VendorComparison $vendorComparison)
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['purchasing', 'admin', 'super_admin'])) {
            return redirect()->route('vendor_comparisons.show', $vendorComparison)
                ->with('error', 'Only purchasing staff can edit Vendor Comparison Forms.');
        }

        if ($vendorComparison->status !== 'draft') {
            return redirect()->route('vendor_comparisons.show', $vendorComparison)
                ->with('error', 'Only draft forms can be edited.');
        }

        $request->validate($this->vendorComparisonRules());

        DB::transaction(function () use ($request, $vendorComparison) {
            $vendorComparison->update([
                'purchase_request_id' => $request->purchase_request_id,
                'tanggal'             => $request->tanggal,
                'detail_barang_jasa'  => $request->detail_barang_jasa,
                'notes'               => $request->notes,
            ]);

            // Vendors and items are fully replaced on every update; prices cascade-delete with them.
            $vendorComparison->vendors()->delete();
            $vendorComparison->items()->delete();

            $this->saveItemsAndVendors($request, $vendorComparison);
        });

        return redirect()->route('vendor_comparisons.show', $vendorComparison)
            ->with('success', 'Vendor Comparison Form updated successfully.');
    }

    public function selectVendor(Request $request, VendorComparison $vendorComparison)
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['director', 'admin', 'super_admin'])) {
            return redirect()->route('vendor_comparisons.show', $vendorComparison)
                ->with('error', 'Only directors can approve and select a vendor.');
        }

        $request->validate([
            'selected_vendor_id' => 'required|exists:vendor_comparison_vendors,id',
        ]);

        // Ensure the selected vendor belongs to this comparison
        $vendor = VendorComparisonVendor::where('id', $request->selected_vendor_id)
            ->where('vendor_comparison_id', $vendorComparison->id)
            ->firstOrFail();

        $vendorComparison->update([
            'selected_vendor_id' => $vendor->id,
            'approved_by'        => $user->id,
            'approved_at'        => now(),
            'status'             => 'approved',
        ]);

        return redirect()->route('vendor_comparisons.show', $vendorComparison)
            ->with('success', 'Vendor selected and form approved successfully.');
    }

    public function submit(VendorComparison $vendorComparison)
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['purchasing', 'admin', 'super_admin'])) {
            return redirect()->route('vendor_comparisons.show', $vendorComparison)
                ->with('error', 'Only purchasing staff can submit this form.');
        }

        if ($vendorComparison->status !== 'draft') {
            return redirect()->route('vendor_comparisons.show', $vendorComparison)
                ->with('error', 'This form has already been submitted.');
        }

        $vendorComparison->update(['status' => 'submitted']);

        return redirect()->route('vendor_comparisons.show', $vendorComparison)
            ->with('success', 'Form submitted for director approval.');
    }

    public function print(VendorComparison $vendorComparison)
    {
        $vendorComparison->load(['vendors.prices', 'items', 'creator', 'approver', 'purchaseRequest', 'selectedVendor']);
        $pdf = Pdf::loadView('vendor_comparisons.print', compact('vendorComparison'))
            ->setPaper('a4', 'portrait');

        // Disable copy & modify; allow printing only
        $pdf->getDomPDF()
            ->getCanvas()
            ->get_cpdf()
            ->setEncryption('', 'fk-pch-owner', ['print']);

        return $pdf->stream('Perbandingan-Vendor-' . $vendorComparison->comparison_number . '.pdf');
    }
}
