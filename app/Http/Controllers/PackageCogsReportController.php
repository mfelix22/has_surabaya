<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PackageCogsReportController extends Controller
{
    private const COATING_CODES = ['AVG', 'CLS', 'ELG', 'SPT'];

    public function index(Request $request)
    {
        if (!auth()->user()?->hasAnyRole(['super_admin', 'admin', 'director', 'manager', 'accounting', 'audit'])) {
            abort(403);
        }

        $packages = Package::orderBy('code')
            ->select('code', 'name', 'category')
            ->get();

        $query = $this->buildBaseQuery($request);

        $totalTransactions = (clone $query)->count();
        $uniqueParts       = (clone $query)->distinct('boi.item_id')->count('boi.item_id');
        $totalCost         = (clone $query)->sum(DB::raw('boi.actual_quantity * COALESCE(boi.unit_cost, 0)'));

        // Summary by Package
        $byPackage = $this->getByPackage((clone $query));

        // Summary by Category (Coating / Ala-Carte)
        $byCategory = $this->getByCategory($byPackage);

        // Summary by Category + Item Type (A/B/C)
        $byItemType = $this->getByCategoryItemType((clone $query));

        // Detailed by Item
        $byItemDetail = $this->getByItemDetail((clone $query));

        // Summary by Work Order with item list (per-COA item breakdown)
        $byWorkOrderItems = $this->getByWorkOrderItems((clone $query));

        $groupExpr = $this->packageGroupExpression();

        // Detailed transactions
        $detailed = (clone $query)
            ->select(
                'bo.id as bon_out_id',
                'bo.issued_date',
                'bo.bon_out_number',
                'wo.id as work_order_id',
                'wo.wo_number',
                'wo.vehicle_plate',
                'wo.vehicle_merk',
                'wo.paket_code',
                'wo.paket_name',
                DB::raw("{$groupExpr} as package_group"),
                'i.id as item_id',
                'i.code as item_code',
                'i.name as item_name',
                'boi.actual_quantity',
                'boi.unit_cost',
                DB::raw('boi.actual_quantity * COALESCE(boi.unit_cost, 0) as line_cost')
            )
            ->orderBy('bo.issued_date', 'desc')
            ->orderBy('bo.id', 'desc')
            ->get();

        return view('reports.package_cogs', compact(
            'packages',
            'totalTransactions',
            'uniqueParts',
            'totalCost',
            'byCategory',
            'byPackage',
            'byItemType',
            'byItemDetail',
            'byWorkOrderItems',
            'detailed'
        ));
    }

    public function export(Request $request)
    {
        if (!auth()->user()?->hasAnyRole(['super_admin', 'admin', 'director', 'manager', 'accounting', 'audit'])) {
            abort(403);
        }

        $tab   = $request->input('tab', 'by_category');
        $query = $this->buildBaseQuery($request);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D6A96']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $cellStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        $filterLabel = $this->buildFilterLabel($request);
        $sheet->setCellValue('A1', 'COGS by Package Report');
        $sheet->setCellValue('A2', $filterLabel);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $row = 4;

        $groupExpr = $this->packageGroupExpression();

        if ($tab === 'by_category') {
            $headers = ['No', 'Category', 'Total Qty Used', 'Usage Count', 'Total Cost (Rp)'];
            foreach ($headers as $col => $h) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . $row, $h);
            }
            $sheet->getStyle("A{$row}:E{$row}")->applyFromArray($headerStyle);
            $row++;

            $byPackage = $this->getByPackage((clone $query));
            $data = $this->getByCategory($byPackage);

            foreach ($data as $n => $r) {
                $cols = [
                    $n + 1,
                    $r->package_group,
                    (float) $r->total_qty,
                    (int) $r->usage_count,
                    (float) $r->total_cost,
                ];
                foreach ($cols as $col => $val) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . $row, $val);
                }
                $sheet->getStyle("A{$row}:E{$row}")->applyFromArray($cellStyle);
                $row++;
            }
            foreach (range(1, 5) as $col) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
            }
            $sheet->setTitle('Summary by Category');
        } elseif ($tab === 'by_package') {
            $headers = ['No', 'Group', 'Package Code', 'Package Name', 'Total Qty Used', 'Usage Count', 'Total Cost (Rp)'];
            foreach ($headers as $col => $h) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . $row, $h);
            }
            $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($headerStyle);
            $row++;

            $data = $this->getByPackage((clone $query));

            foreach ($data as $n => $r) {
                $cols = [
                    $n + 1,
                    $r->package_group,
                    $r->paket_code,
                    $r->paket_name,
                    (float) $r->total_qty,
                    (int) $r->usage_count,
                    (float) $r->total_cost,
                ];
                foreach ($cols as $col => $val) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . $row, $val);
                }
                $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($cellStyle);
                $row++;
            }
            foreach (range(1, 7) as $col) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
            }
            $sheet->setTitle('Summary by Package');
        } elseif ($tab === 'by_item_type') {
            $headers = ['No', 'Package Category', 'Item Type', 'Item Type Name', 'Total Qty Used', 'Usage Count', 'Total Cost (Rp)'];
            foreach ($headers as $col => $h) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . $row, $h);
            }
            $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($headerStyle);
            $row++;

            $data = $this->getByCategoryItemType((clone $query));

            foreach ($data as $n => $r) {
                $cols = [
                    $n + 1,
                    $r->package_group,
                    $r->item_type,
                    $r->item_type_name,
                    (float) $r->total_qty,
                    (int) $r->usage_count,
                    (float) $r->total_cost,
                ];
                foreach ($cols as $col => $val) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . $row, $val);
                }
                $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($cellStyle);
                $row++;
            }
            foreach (range(1, 7) as $col) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
            }
            $sheet->setTitle('Summary by Item Type');
        } elseif ($tab === 'by_item_detail') {
            $headers = ['No', 'Package Category', 'Item Type', 'Item Type Name', 'Item Code', 'Item Name', 'Total Qty Used', 'Usage Count', 'Avg Price (Rp)', 'Total COGS (Rp)'];
            foreach ($headers as $col => $h) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . $row, $h);
            }
            $sheet->getStyle("A{$row}:J{$row}")->applyFromArray($headerStyle);
            $row++;

            $data = $this->getByItemDetail((clone $query));

            foreach ($data as $n => $r) {
                $cols = [
                    $n + 1,
                    $r->package_group,
                    $r->item_type,
                    $r->item_type_name,
                    $r->item_code,
                    $r->item_name,
                    (float) $r->total_qty,
                    (int) $r->usage_count,
                    (float) $r->avg_price,
                    (float) $r->total_cost,
                ];
                foreach ($cols as $col => $val) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . $row, $val);
                }
                $sheet->getStyle("A{$row}:J{$row}")->applyFromArray($cellStyle);
                $row++;
            }
            foreach (range(1, 10) as $col) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
            }
            $sheet->setTitle('Detailed by Item');
        } elseif ($tab === 'by_work_order_items') {
            $headers = ['No', 'WO #', 'Vehicle', 'Merk', 'Package', 'Package Group', 'Item Type', 'Item Code', 'Item Name', 'Qty Used', 'Avg Price (Rp)', 'Total COGS (Rp)'];
            foreach ($headers as $col => $h) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . $row, $h);
            }
            $sheet->getStyle("A{$row}:L{$row}")->applyFromArray($headerStyle);
            $row++;

            $data = $this->getByWorkOrderItems((clone $query));

            foreach ($data as $n => $r) {
                $cols = [
                    $n + 1,
                    $r->wo_number,
                    $r->vehicle_plate,
                    $r->vehicle_merk,
                    $r->paket_name,
                    $r->package_group,
                    $r->item_type,
                    $r->item_code,
                    $r->item_name,
                    (float) $r->total_qty,
                    (float) $r->avg_price,
                    (float) $r->total_cost,
                ];
                foreach ($cols as $col => $val) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . $row, $val);
                }
                $sheet->getStyle("A{$row}:L{$row}")->applyFromArray($cellStyle);
                $row++;
            }
            foreach (range(1, 12) as $col) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
            }
            $sheet->setTitle('Summary by Work Order Items');
        } else {
            $headers = ['No', 'Date', 'Bon Out #', 'WO #', 'Vehicle', 'Merk', 'Group', 'Package Code', 'Package Name', 'Part Code', 'Part Name', 'Qty', 'Unit Cost (Rp)', 'Total (Rp)'];
            foreach ($headers as $col => $h) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . $row, $h);
            }
            $sheet->getStyle("A{$row}:N{$row}")->applyFromArray($headerStyle);
            $row++;

            $data = (clone $query)
                ->select(
                    'bo.issued_date',
                    'bo.bon_out_number',
                    'wo.wo_number',
                    'wo.vehicle_plate',
                    'wo.vehicle_merk',
                    'wo.paket_code',
                    'wo.paket_name',
                    DB::raw("{$groupExpr} as package_group"),
                    'i.code as item_code',
                    'i.name as item_name',
                    'boi.actual_quantity',
                    'boi.unit_cost',
                    DB::raw('boi.actual_quantity * COALESCE(boi.unit_cost, 0) as line_cost')
                )
                ->orderBy('bo.issued_date', 'desc')
                ->orderBy('bo.id', 'desc')
                ->get();

            foreach ($data as $n => $r) {
                $cols = [
                    $n + 1,
                    $r->issued_date,
                    $r->bon_out_number,
                    $r->wo_number,
                    $r->vehicle_plate,
                    $r->vehicle_merk,
                    $r->package_group,
                    $r->paket_code,
                    $r->paket_name,
                    $r->item_code,
                    $r->item_name,
                    (float) $r->actual_quantity,
                    (float) $r->unit_cost,
                    (float) $r->line_cost,
                ];
                foreach ($cols as $col => $val) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . $row, $val);
                }
                $sheet->getStyle("A{$row}:N{$row}")->applyFromArray($cellStyle);
                $row++;
            }
            foreach (range(1, 14) as $col) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
            }
            $sheet->setTitle('Detailed Transactions');
        }

        $filename = 'package_cogs_report_' . now()->format('Ymd_His') . '.xlsx';
        $writer   = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function buildBaseQuery(Request $request)
    {
        $q = DB::table('bon_out_items as boi')
            ->join('bon_outs as bo', 'bo.id', '=', 'boi.bon_out_id')
            ->join('work_orders as wo', 'wo.id', '=', 'bo.work_order_id')
            ->join('items as i', 'i.id', '=', 'boi.item_id')
            ->where('bo.status', 'completed')
            ->whereNotNull('boi.actual_quantity')
            ->where('boi.actual_quantity', '>', 0);

        if ($request->filled('date_from')) {
            $q->whereDate('bo.issued_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->whereDate('bo.issued_date', '<=', $request->date_to);
        }
        if ($request->filled('category')) {
            switch ($request->category) {
                case 'coating':
                    $q->whereIn('wo.paket_code', self::COATING_CODES);
                    break;
                case 'ala_carte':
                    $q->whereNotIn('wo.paket_code', self::COATING_CODES)
                      ->whereNotNull('wo.paket_code')
                      ->where('wo.paket_code', '!=', '');
                    break;
                case 'no_package':
                    $q->where(function ($sub) {
                        $sub->whereNull('wo.paket_code')->orWhere('wo.paket_code', '');
                    });
                    break;
            }
        }
        if ($request->filled('package_codes')) {
            $codes = is_array($request->package_codes) ? $request->package_codes : [$request->package_codes];
            $codes = array_filter($codes, fn ($c) => $c !== '' && $c !== null);
            if ($codes) {
                $q->whereIn('wo.paket_code', $codes);
            }
        }
        if ($request->filled('wo_number')) {
            $q->where('wo.wo_number', 'like', '%' . $request->wo_number . '%');
        }

        return $q;
    }

    private function getByPackage($query)
    {
        return $query
            ->select(
                'wo.paket_code',
                'wo.paket_name',
                DB::raw('SUM(boi.actual_quantity) as total_qty'),
                DB::raw('COUNT(boi.id) as usage_count'),
                DB::raw('SUM(boi.actual_quantity * COALESCE(boi.unit_cost, 0)) as total_cost')
            )
            ->groupBy('wo.paket_code', 'wo.paket_name')
            ->orderByDesc('total_cost')
            ->get()
            ->each(fn ($row) => $row->package_group = $this->packageGroupFor($row->paket_code));
    }

    private function getByCategory($byPackage)
    {
        return $byPackage->groupBy('package_group')
            ->map(fn ($rows, $group) => (object) [
                'package_group' => $group,
                'total_qty'     => $rows->sum('total_qty'),
                'usage_count'   => $rows->sum('usage_count'),
                'total_cost'    => $rows->sum('total_cost'),
            ])
            ->sortByDesc('total_cost')
            ->values();
    }

    private function getByCategoryItemType($query)
    {
        $typeNames = Item::getItemTypes();

        $raw = $query
            ->select(
                'wo.paket_code',
                'i.item_type',
                DB::raw('SUM(boi.actual_quantity) as total_qty'),
                DB::raw('COUNT(boi.id) as usage_count'),
                DB::raw('SUM(boi.actual_quantity * COALESCE(boi.unit_cost, 0)) as total_cost')
            )
            ->groupBy('wo.paket_code', 'i.item_type')
            ->orderByDesc('total_cost')
            ->get();

        return $raw
            ->map(fn ($row) => (object) [
                'package_group'  => $this->packageGroupFor($row->paket_code),
                'item_type'      => $row->item_type,
                'item_type_name' => $typeNames[$row->item_type] ?? $row->item_type,
                'total_qty'      => $row->total_qty,
                'usage_count'    => $row->usage_count,
                'total_cost'     => $row->total_cost,
            ])
            ->groupBy(fn ($row) => $row->package_group . '|' . $row->item_type)
            ->map(fn ($rows, $key) => (object) [
                'package_group'  => $rows->first()->package_group,
                'item_type'      => $rows->first()->item_type,
                'item_type_name' => $rows->first()->item_type_name,
                'total_qty'      => $rows->sum('total_qty'),
                'usage_count'    => $rows->sum('usage_count'),
                'total_cost'     => $rows->sum('total_cost'),
            ])
            ->sortByDesc('total_cost')
            ->values();
    }

    private function getByItemDetail($query)
    {
        $typeNames = Item::getItemTypes();

        $raw = $query
            ->select(
                'wo.paket_code',
                'i.id as item_id',
                'i.code as item_code',
                'i.name as item_name',
                'i.item_type',
                DB::raw('SUM(boi.actual_quantity) as total_qty'),
                DB::raw('COUNT(boi.id) as usage_count'),
                DB::raw('SUM(boi.actual_quantity * COALESCE(boi.unit_cost, 0)) as total_cost')
            )
            ->groupBy('wo.paket_code', 'i.id', 'i.item_type', 'i.code', 'i.name')
            ->orderByDesc('total_cost')
            ->get();

        return $raw
            ->map(fn ($row) => (object) [
                'package_group'  => $this->packageGroupFor($row->paket_code),
                'item_type'      => $row->item_type,
                'item_type_name' => $typeNames[$row->item_type] ?? $row->item_type,
                'item_id'        => $row->item_id,
                'item_code'      => $row->item_code,
                'item_name'      => $row->item_name,
                'total_qty'      => $row->total_qty,
                'usage_count'    => $row->usage_count,
                'total_cost'     => $row->total_cost,
            ])
            ->groupBy(fn ($row) => $row->package_group . '|' . $row->item_id)
            ->map(fn ($rows, $key) => (object) [
                'package_group'  => $rows->first()->package_group,
                'item_type'      => $rows->first()->item_type,
                'item_type_name' => $rows->first()->item_type_name,
                'item_id'        => $rows->first()->item_id,
                'item_code'      => $rows->first()->item_code,
                'item_name'      => $rows->first()->item_name,
                'total_qty'      => $rows->sum('total_qty'),
                'usage_count'    => $rows->sum('usage_count'),
                'total_cost'     => $rows->sum('total_cost'),
                'avg_price'      => $rows->sum('total_qty') > 0
                    ? $rows->sum('total_cost') / $rows->sum('total_qty')
                    : 0,
            ])
            ->sortByDesc('total_cost')
            ->values();
    }

    private function getByWorkOrderItems($query)
    {
        $typeNames = Item::getItemTypes();

        $raw = $query
            ->select(
                'wo.id as work_order_id',
                'wo.wo_number',
                'wo.vehicle_plate',
                'wo.vehicle_merk',
                'wo.paket_code',
                'wo.paket_name',
                'i.id as item_id',
                'i.code as item_code',
                'i.name as item_name',
                'i.item_type',
                DB::raw('SUM(boi.actual_quantity) as total_qty'),
                DB::raw('SUM(boi.actual_quantity * COALESCE(boi.unit_cost, 0)) as total_cost')
            )
            ->groupBy('wo.id', 'wo.wo_number', 'wo.vehicle_plate', 'wo.vehicle_merk', 'wo.paket_code', 'wo.paket_name', 'i.id', 'i.code', 'i.name', 'i.item_type')
            ->orderByDesc('wo.id')
            ->orderBy('i.code')
            ->get();

        return $raw
            ->map(fn ($row) => (object) [
                'work_order_id'  => $row->work_order_id,
                'wo_number'      => $row->wo_number,
                'vehicle_plate'  => $row->vehicle_plate,
                'vehicle_merk'   => $row->vehicle_merk,
                'paket_code'     => $row->paket_code,
                'paket_name'     => $row->paket_name,
                'package_group'  => $this->packageGroupFor($row->paket_code),
                'item_id'        => $row->item_id,
                'item_code'      => $row->item_code,
                'item_name'      => $row->item_name,
                'item_type'      => $row->item_type,
                'item_type_name' => $typeNames[$row->item_type] ?? $row->item_type,
                'total_qty'      => $row->total_qty,
                'total_cost'     => $row->total_cost,
                'avg_price'      => $row->total_qty > 0
                    ? $row->total_cost / $row->total_qty
                    : 0,
            ])
            ->sortByDesc('total_cost')
            ->values();
    }

    private function packageGroupFor(?string $code): string
    {
        if (empty($code)) {
            return 'No Package';
        }

        return in_array($code, self::COATING_CODES, true) ? 'Coating' : 'Ala-Carte';
    }

    private function packageGroupExpression(): string
    {
        $codes = "'" . implode("', '", self::COATING_CODES) . "'";
        return "CASE WHEN wo.paket_code IN ({$codes}) THEN 'Coating' WHEN wo.paket_code IS NULL OR wo.paket_code = '' THEN 'No Package' ELSE 'Ala-Carte' END";
    }

    private function buildFilterLabel(Request $request): string
    {
        $parts = [];
        if ($request->filled('date_from'))  $parts[] = 'From: ' . $request->date_from;
        if ($request->filled('date_to'))    $parts[] = 'To: '   . $request->date_to;
        if ($request->filled('category'))   $parts[] = 'Category: ' . ucwords(str_replace('_', ' ', $request->category));
        if ($request->filled('package_codes')) {
            $codes = is_array($request->package_codes) ? $request->package_codes : [$request->package_codes];
            $parts[] = 'Package: ' . implode(', ', $codes);
        }
        if ($request->filled('wo_number'))  $parts[] = 'WO #: ' . $request->wo_number;
        return $parts ? implode(' | ', $parts) : 'All Data';
    }
}
