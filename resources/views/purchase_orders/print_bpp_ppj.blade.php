<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>BPP - {{ $purchaseOrder->po_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            padding: 12px;
        }

        .bold {
            font-weight: bold;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        /* Outer form box — 20 column grid */
        .form-table {
            width: 100%;
            border: 1.5px solid #000;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .form-table td,
        .form-table th {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: top;
            word-wrap: break-word;
        }

        .form-table .nb {
            border: none;
        }

        /* Header */
        .company-name {
            font-weight: bold;
            font-size: 13px;
        }

        .company-details {
            font-size: 10px;
            line-height: 1.4;
        }

        /* Title */
        .title-cell {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            padding: 5px;
        }

        /* Invoice table */
        .inv-head {
            font-weight: bold;
            text-align: center;
            padding: 5px 6px;
        }

        .inv-cell {
            height: 18px;
        }

        .cbx {
            display: inline-block;
            width: 11px;
            height: 11px;
            border: 1.5px solid #000;
            text-align: center;
            line-height: 11px;
            font-weight: bold;
            font-size: 9px;
            vertical-align: middle;
            margin-right: 6px;
        }

        .sig-label {
            font-weight: bold;
            text-align: center;
        }

        .sig-space {
            height: 55px;
            text-align: center;
            vertical-align: bottom;
        }

        .sig-role {
            text-align: center;
            font-weight: bold;
        }

        .footer {
            margin-top: 10px;
            border-top: 1px solid #000;
            padding-top: 4px;
            font-size: 9px;
        }
    </style>
</head>

<body>
    @php
        use App\Helpers\NumberToIndonesian;

        $bppNumber = 'BPP-' . ($purchaseOrder->ppb_number ?? str_replace('/', '-', $purchaseOrder->po_number));
        $payee = $purchaseOrder->supplier->name ?? ($purchaseOrder->supplier_name ?? '-');
        $bppDate = now()->format('d M Y');

        $hasNota = $purchaseOrder->status === 'completed' && $purchaseOrder->nomor_nota;
        $invoiceTotal = (float) $purchaseOrder->purchaseInvoices->sum('total_amount');
        $notaAmount = $hasNota ? (float) $purchaseOrder->total_amount : 0;
        $subTotal = $invoiceTotal + $notaAmount;
        $miscTotal = $purchaseOrder->miscCosts->sum('amount');

        $pph = 0;
        $pphLabel = null;
        if ($purchaseOrder->pph_type === 'pph_21') {
            $pph = $subTotal * 0.025;
            $pphLabel = 'Potongan PPh 21 (2.5%)';
        } elseif ($purchaseOrder->pph_type === 'pph_23') {
            $pph = $subTotal * 0.02;
            $pphLabel = 'Potongan PPh 23 (2%)';
        }

        $grandTotal = $subTotal + $miscTotal - $pph;
        $terbilang = NumberToIndonesian::convert((int) $grandTotal);

        $dueDates = $purchaseOrder->purchaseInvoices->pluck('due_date')->filter();
        $jatuhTempo = $dueDates->isNotEmpty()
            ? $dueDates->max()->format('d M Y')
            : ($purchaseOrder->jatuh_tempo ?? '');
        $notaDate = $purchaseOrder->closed_at?->format('d M Y') ?? '-';

        $serviceDesc = $purchaseOrder->details
            ->pluck('service_description')
            ->filter()
            ->implode('; ');
        $notaNotes = 'Pembayaran Jasa ' . $purchaseOrder->po_number
            . ($serviceDesc ? ' - ' . $serviceDesc : '');

        $payTunai = $purchaseOrder->pembayaran === 'tunai';
        $payTransfer = $purchaseOrder->pembayaran === 'non_tunai';
        $payCekBg = false;

        $requester = optional($purchaseOrder->purchaseInvoices->first())->recorder->name
            ?? (optional(optional($purchaseOrder->purchaseRequest)->requester)->name
                ?? (optional($purchaseOrder->creator)->name ?? ''));

        $rowCount = $purchaseOrder->purchaseInvoices->count()
            + ($hasNota ? 1 : 0)
            + $purchaseOrder->miscCosts->count()
            + ($pph > 0 ? 1 : 0);
    @endphp

    <table class="form-table">
        <colgroup>
            @for ($c = 0; $c < 20; $c++)
                <col style="width:5%;">
            @endfor
        </colgroup>

        <!-- HEADER -->
        <tr>
            <td colspan="20" style="padding:8px;">
                <table style="width:100%; border-collapse:collapse;">
                    <tr>
                        <td style="width:60%; border:none; vertical-align:top; padding:0;">
                            <div class="company-name">PT HARTONO AUTO STUDIO</div>
                            <div class="company-details">
                                Auto Care &amp; Lifestyle Solutions<br>
                                Jl. Demak No. 166-170 RT 012 RW 006, Gundih, Bubutan<br>
                                Kota Surabaya 60172, Indonesia
                            </div>
                        </td>
                        <td style="width:40%; border:none; text-align:right; vertical-align:top; padding:0;">
                            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('HAS.png'))) }}"
                                alt="Logo" style="height:58px; width:auto;">
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- TITLE -->
        <tr>
            <td colspan="20" class="title-cell">BUKTI PERMINTAAN PEMBAYARAN</td>
        </tr>
        <tr>
            <td colspan="20" class="center"><strong>No :</strong> {{ $bppNumber }}</td>
        </tr>

        <!-- PAYEE + DATE -->
        <tr>
            <td colspan="11"><strong>Dibayarkan kepada:</strong> {{ $payee }}</td>
            <td colspan="9"><strong>Tanggal:</strong> {{ $bppDate }}</td>
        </tr>

        <!-- INVOICE TABLE -->
        <tr>
            <td colspan="4" class="inv-head">No. Nota/Invoice</td>
            <td colspan="4" class="inv-head">Tgl Nota/Invoice</td>
            <td colspan="4" class="inv-head">Jumlah</td>
            <td colspan="8" class="inv-head">Keterangan</td>
        </tr>
        @foreach ($purchaseOrder->purchaseInvoices as $invoice)
            <tr>
                <td colspan="4" class="inv-cell">{{ $invoice->invoice_number }}</td>
                <td colspan="4" class="inv-cell center">{{ $invoice->invoice_date?->format('d M Y') ?? '-' }}</td>
                <td colspan="4" class="inv-cell right">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                <td colspan="8" class="inv-cell">{{ $invoice->notes ?: ($invoice->is_prepayment ? 'Proforma Invoice - ' . $purchaseOrder->po_number : 'Pembayaran ' . $purchaseOrder->po_number) }}</td>
            </tr>
        @endforeach
        @if ($hasNota)
            <tr>
                <td colspan="4" class="inv-cell">{{ $purchaseOrder->nomor_nota }}</td>
                <td colspan="4" class="inv-cell center">{{ $notaDate }}</td>
                <td colspan="4" class="inv-cell right">Rp {{ number_format($notaAmount, 0, ',', '.') }}</td>
                <td colspan="8" class="inv-cell">{{ $notaNotes }}</td>
            </tr>
        @endif
        @foreach ($purchaseOrder->miscCosts as $misc)
            <tr>
                <td colspan="4" class="inv-cell">-</td>
                <td colspan="4" class="inv-cell center">-</td>
                <td colspan="4" class="inv-cell right">Rp {{ number_format($misc->amount, 0, ',', '.') }}</td>
                <td colspan="8" class="inv-cell">{{ $misc->description }}</td>
            </tr>
        @endforeach
        @if ($pph > 0)
            <tr>
                <td colspan="4" class="inv-cell">-</td>
                <td colspan="4" class="inv-cell center">-</td>
                <td colspan="4" class="inv-cell right">(Rp {{ number_format($pph, 0, ',', '.') }})</td>
                <td colspan="8" class="inv-cell">{{ $pphLabel }}</td>
            </tr>
        @endif
        @for ($i = $rowCount; $i < 6; $i++)
            <tr>
                <td colspan="4" class="inv-cell">&nbsp;</td>
                <td colspan="4" class="inv-cell">&nbsp;</td>
                <td colspan="4" class="inv-cell">&nbsp;</td>
                <td colspan="8" class="inv-cell">&nbsp;</td>
            </tr>
        @endfor

        <!-- TOTAL -->
        <tr>
            <td colspan="8" class="center bold">TOTAL</td>
            <td colspan="4" class="right bold">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
            <td colspan="8">&nbsp;</td>
        </tr>

        <!-- TERBILANG -->
        <tr>
            <td colspan="8" class="center bold">TERBILANG</td>
            <td colspan="12"><em>{{ $terbilang }} Rupiah</em></td>
        </tr>

        <!-- DUE DATE + PAYMENT METHOD -->
        <tr>
            <td colspan="4"><strong>Tanggal Jatuh Tempo</strong><br><br>{{ $jatuhTempo }}</td>
            <td colspan="4" style="vertical-align:middle;"><strong>Pembayaran Via</strong></td>
            <td colspan="12" style="vertical-align:middle;">
                <span class="cbx">@if ($payTunai) V @endif</span> Tunai
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <span class="cbx">@if ($payCekBg) V @endif</span> Cek / BG
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <span class="cbx">@if ($payTransfer) V @endif</span> Transfer
            </td>
        </tr>
        <tr>
            <td colspan="20"><strong>Rekening Bank:</strong> {{ $purchaseOrder->bank_account ?? '' }}</td>
        </tr>

        <!-- SIGNATURES -->
        <tr>
            <td colspan="4" class="sig-label">Diminta oleh:</td>
            <td colspan="4" class="sig-label">Disetujui oleh:</td>
            <td colspan="8" class="sig-label">Diperiksa oleh:</td>
            <td colspan="4" class="sig-label">Dibayarkan oleh:</td>
        </tr>
        <tr>
            <td colspan="4" class="sig-space">{{ $requester }}</td>
            <td colspan="4" class="sig-space">&nbsp;</td>
            <td colspan="4" class="sig-space">&nbsp;</td>
            <td colspan="4" class="sig-space">&nbsp;</td>
            <td colspan="4" class="sig-space">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="4" class="sig-role">User</td>
            <td colspan="4" class="sig-role">User Dept Head</td>
            <td colspan="4" class="sig-role">SPV Finance</td>
            <td colspan="4" class="sig-role">Accounting</td>
            <td colspan="4" class="sig-role">Cashier</td>
        </tr>
        <tr>
            <td colspan="4"><strong>Tgl:</strong></td>
            <td colspan="4"><strong>Tgl:</strong></td>
            <td colspan="4"><strong>Tgl:</strong></td>
            <td colspan="4"><strong>Tgl:</strong></td>
            <td colspan="4"><strong>Tgl:</strong></td>
        </tr>
    </table>

    <!-- FOOTER -->
    <div class="footer">
        hartonomotor-group.com/hrautostudio | hrautostudio@hartonomotor.com
    </div>

</body>

</html>
