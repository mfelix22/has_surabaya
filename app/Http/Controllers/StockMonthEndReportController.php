<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StockMonthEndReportController extends Controller
{
    private const ROLES = ['super_admin', 'admin', 'director', 'manager', 'accounting', 'audit'];

    public function index(Request $request)
    {
        if (!auth()->user()?->hasAnyRole(self::ROLES)) {
            abort(403);
        }

        [$month, $year] = $this->resolvePeriod($request);
        $rows = $this->buildRows($month, $year, $request);

        $itemTypes = DB::table('items')->select('item_type')
            ->distinct()->orderBy('item_type')->pluck('item_type');

        $years = DB::table('stock_transactions')
            ->selectRaw('YEAR(created_at) as year')
            ->union(DB::table('stocks')->selectRaw('YEAR(created_at) as year'))
            ->distinct()->orderByDesc('year')->pluck('year');

        return view('reports.stock_month_end', [
            'rows'        => $rows,
            'month'       => $month,
            'year'        => $year,
            'years'       => $years,
            'itemTypes'   => $itemTypes,
            'totalValue'  => $rows->sum('closing_value'),
            'periodLabel' => Carbon::createFromDate($year, $month, 1)->format('F Y'),
        ]);
    }

    public function export(Request $request)
    {
        if (!auth()->user()?->hasAnyRole(self::ROLES)) {
            abort(403);
        }

        [$month, $year] = $this->resolvePeriod($request);
        $rows      = $this->buildRows($month, $year, $request);
        $periodLbl = Carbon::createFromDate($year, $month, 1)->format('F Y');

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Month-End Stock Cost');

        $sheet->setCellValue('A1', 'Month-End Stock Cost Report');
        $sheet->setCellValue('A2', 'Period: ' . $periodLbl);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D6A96']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $cellStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        $headers = ['No', 'Item Code', 'Item Name', 'Item Type', 'Closing Qty', 'UOM', 'Avg Cost (Rp)', 'Stock Value (Rp)'];
        $row     = 4;
        foreach ($headers as $col => $h) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . $row, $h);
        }
        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($headerStyle);
        $row++;

        foreach ($rows as $n => $r) {
            $cols = [
                $n + 1,
                $r->item_code,
                $r->item_name,
                $r->item_type,
                $r->closing_qty,
                $r->uom_code,
                $r->avg_cost,
                $r->closing_value,
            ];
            foreach ($cols as $col => $val) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . $row, $val);
            }
            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("G{$row}:H{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($cellStyle);
            $row++;
        }

        $sheet->setCellValue('G' . $row, 'TOTAL');
        $sheet->getStyle('G' . $row)->getFont()->setBold(true);
        $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('H' . $row, $rows->sum('closing_value'));
        $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('H' . $row)->getFont()->setBold(true);

        foreach (range(1, 8) as $col) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
        }

        $filename = 'stock_month_end_' . Carbon::createFromDate($year, $month, 1)->format('Y_m') . '.xlsx';
        $writer   = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    // -------------------------------------------------------------------------

    private function resolvePeriod(Request $request): array
    {
        // Default to the previous month — the most recently closed period
        $default = Carbon::now()->subMonth();

        $month = (int) $request->input('month', $default->month);
        $year  = (int) $request->input('year', $default->year);

        if ($month < 1 || $month > 12) {
            $month = $default->month;
        }
        if ($year < 2000 || $year > (int) date('Y') + 1) {
            $year = $default->year;
        }

        return [$month, $year];
    }

    /**
     * For each item with a stock record, compute closing qty and closing
     * average cost as at the end of the requested month.
     */
    private function buildRows(int $month, int $year, Request $request): Collection
    {
        $monthEnd = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();

        $stocks = Stock::query()
            ->join('items', 'stocks.item_id', '=', 'items.id')
            ->leftJoin('uoms', 'items.smallest_uom_id', '=', 'uoms.id')
            ->where('stocks.location', 'default')
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = trim($request->search);
                $q->where(function ($w) use ($s) {
                    $w->where('items.code', 'like', "%{$s}%")
                        ->orWhere('items.name', 'like', "%{$s}%");
                });
            })
            ->when($request->filled('item_type'), fn ($q) => $q->where('items.item_type', $request->item_type))
            ->orderBy('items.name')
            ->get([
                'stocks.item_id',
                'stocks.quantity as current_qty',
                'stocks.avg_cost as current_avg',
                'stocks.created_at as stock_created_at',
                'items.code as item_code',
                'items.name as item_name',
                'items.item_type',
                'uoms.code as uom_code',
            ]);

        $itemIds = $stocks->pluck('item_id');

        // Latest transaction per item on/before month end (id grows with created_at)
        $lastTxnIds = DB::table('stock_transactions')
            ->whereIn('item_id', $itemIds)
            ->where('created_at', '<=', $monthEnd)
            ->select('item_id', DB::raw('MAX(id) as last_id'))
            ->groupBy('item_id')
            ->pluck('last_id', 'item_id');

        $lastTxns = StockTransaction::whereIn('id', $lastTxnIds->values())->get()->keyBy('item_id');

        // For rows whose last transaction has no avg_cost_after (pre-backfill),
        // fall back to the most recent transaction that does have one
        $needAvgFallback = $lastTxns->filter(fn ($t) => $t->avg_cost_after === null)->keys();
        $avgFallbacks    = collect();
        if ($needAvgFallback->isNotEmpty()) {
            $fbIds = DB::table('stock_transactions')
                ->whereIn('item_id', $needAvgFallback)
                ->where('created_at', '<=', $monthEnd)
                ->whereNotNull('avg_cost_after')
                ->select('item_id', DB::raw('MAX(id) as last_id'))
                ->groupBy('item_id')
                ->pluck('last_id', 'item_id');
            $avgFallbacks = StockTransaction::whereIn('id', $fbIds->values())->get()->keyBy('item_id');
        }

        // Earliest transaction per item after month end (for qty fallback)
        $firstTxnIds = DB::table('stock_transactions')
            ->whereIn('item_id', $itemIds)
            ->where('created_at', '>', $monthEnd)
            ->select('item_id', DB::raw('MIN(id) as first_id'))
            ->groupBy('item_id')
            ->pluck('first_id', 'item_id');

        $firstTxns = StockTransaction::whereIn('id', $firstTxnIds->values())->get()->keyBy('item_id');

        // Cost adjustments around the boundary (covers adjustments made before
        // the ledger started recording COST_ADJUSTMENT transactions)
        $lastAdjIds = DB::table('stock_cost_adjustments')
            ->whereIn('item_id', $itemIds)
            ->where('created_at', '<=', $monthEnd)
            ->select('item_id', DB::raw('MAX(id) as last_id'))
            ->groupBy('item_id')
            ->pluck('last_id', 'item_id');

        $lastAdjs = DB::table('stock_cost_adjustments')->whereIn('id', $lastAdjIds->values())
            ->get()->keyBy('item_id');

        $firstAdjIds = DB::table('stock_cost_adjustments')
            ->whereIn('item_id', $itemIds)
            ->where('created_at', '>', $monthEnd)
            ->select('item_id', DB::raw('MIN(id) as first_id'))
            ->groupBy('item_id')
            ->pluck('first_id', 'item_id');

        $firstAdjs = DB::table('stock_cost_adjustments')->whereIn('id', $firstAdjIds->values())
            ->get()->keyBy('item_id');

        return $stocks->map(function ($s) use ($monthEnd, $lastTxns, $avgFallbacks, $firstTxns, $lastAdjs, $firstAdjs) {
            $itemId = $s->item_id;
            $txn    = $lastTxns->get($itemId);
            $first  = $firstTxns->get($itemId);

            // ---- Closing quantity ----
            if ($txn) {
                $closingQty = (float) $txn->balance_after;
            } elseif ($first) {
                // No transaction yet in/≤ this month: qty = balance before the first later txn
                $closingQty = (float) $first->balance_after - (float) $first->quantity;
            } else {
                // No transactions at all: stock has not moved since creation
                $closingQty = $s->stock_created_at <= $monthEnd ? (float) $s->current_qty : 0.0;
            }

            // ---- Closing average cost ----
            $avg   = null;
            $avgAt = null;

            $avgTxn = ($txn && $txn->avg_cost_after !== null) ? $txn : $avgFallbacks->get($itemId);
            if ($avgTxn) {
                $avg   = (float) $avgTxn->avg_cost_after;
                $avgAt = $avgTxn->created_at;
            }

            $adj = $lastAdjs->get($itemId);
            if ($adj && (!$avgAt || $adj->created_at > $avgAt)) {
                $avg   = (float) $adj->new_avg_cost;
                $avgAt = $adj->created_at;
            }

            if ($avg === null) {
                // No cost history before month end: if the cost was adjusted
                // later, the pre-adjustment cost applies; else current cost
                $postAdj = $firstAdjs->get($itemId);
                $avg     = $postAdj ? (float) $postAdj->old_avg_cost : (float) $s->current_avg;
            }

            return (object) [
                'item_id'       => $itemId,
                'item_code'     => $s->item_code,
                'item_name'     => $s->item_name,
                'item_type'     => $s->item_type,
                'uom_code'      => $s->uom_code,
                'closing_qty'   => $closingQty,
                'avg_cost'      => $avg,
                'closing_value' => $closingQty * $avg,
            ];
        })->values();
    }
}
