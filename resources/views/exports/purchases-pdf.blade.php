@php use App\Support\Money; @endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Nota Pembelian</title>
    <style>
        * {
            font-family: DejaVu Sans, sans-serif;
        }

        body {
            font-size: 11px;
            color: #111;
        }

        h1 {
            font-size: 16px;
            margin: 0 0 4px;
        }

        .meta {
            color: #555;
            font-size: 10px;
            margin-bottom: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 4px 6px;
            text-align: left;
        }

        th {
            background: #f0f0f0;
            font-size: 10px;
            text-transform: uppercase;
        }

        td.num {
            text-align: right;
            white-space: nowrap;
        }

        tfoot td {
            font-weight: bold;
            background: #fafafa;
        }
    </style>
</head>

<body>
    <h1>Laporan Nota Pembelian</h1>
    <div class="meta">
        Dicetak: {{ now()->format('d M Y H:i') }} &middot; Total nota: {{ $purchases->count() }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Tanggal</th>
                <th>Supplier</th>
                <th>Item</th>
                <th>Subtotal</th>
                <th>Diskon Item</th>
                <th>Diskon Bundle</th>
                <th>Grand Total</th>
            </tr>
        </thead>
        <tbody>
            @php $grandSum = '0'; @endphp
            @forelse ($purchases as $purchase)
                @php $grandSum = Money::add($grandSum, (string) $purchase->grand_total); @endphp
                <tr>
                    <td>{{ $purchase->kode }}</td>
                    <td>{{ $purchase->tanggal?->format('d M Y') ?? '-' }}</td>
                    <td>{{ $purchase->supplier?->nama ?? 'Lain-lain' }}</td>
                    <td class="num">{{ $purchase->items_count }}</td>
                    <td class="num">{{ Money::format((string) $purchase->subtotal) }}</td>
                    <td class="num">{{ Money::format((string) $purchase->total_diskon_item) }}</td>
                    <td class="num">{{ Money::format((string) $purchase->total_diskon_bundle) }}</td>
                    <td class="num">{{ Money::format((string) $purchase->grand_total) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center; padding:20px; color:#888;">
                        Tidak ada nota untuk filter ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if ($purchases->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="7" class="num">Total</td>
                    <td class="num">{{ Money::format($grandSum) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</body>

</html>
