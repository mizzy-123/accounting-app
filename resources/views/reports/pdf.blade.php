<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 18px 0 8px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        .meta { color: #555; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; }
        td.num, th.num { text-align: right; }
        .total { font-weight: bold; }
        .ok { color: #047857; }
        .bad { color: #b91c1c; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">
        <div>{{ $entity->name }} ({{ $entity->type }})</div>
        <div>Dibuat: {{ $generatedAt }}</div>
    </div>

    @if ($type === 'cash_flow')
        <h2>Ringkasan Bulanan</h2>
        <table>
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th class="num">Pemasukan</th>
                    <th class="num">Pengeluaran</th>
                    <th class="num">Net</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payload['monthly'] as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td class="num">{{ number_format((float) $row['income'], 0, ',', '.') }}</td>
                        <td class="num">{{ number_format((float) $row['expense'], 0, ',', '.') }}</td>
                        <td class="num">{{ number_format((float) $row['net'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td>Total</td>
                    <td class="num">{{ number_format((float) $payload['totals']['income'], 0, ',', '.') }}</td>
                    <td class="num">{{ number_format((float) $payload['totals']['expense'], 0, ',', '.') }}</td>
                    <td class="num">{{ number_format((float) $payload['totals']['net'], 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <h2>Breakdown Kategori</h2>
        <table>
            <thead>
                <tr><th>Kategori</th><th>Tipe</th><th class="num">Jumlah</th></tr>
            </thead>
            <tbody>
                @forelse ($payload['categories'] as $cat)
                    <tr>
                        <td>{{ $cat['name'] }}</td>
                        <td>{{ $cat['type'] }}</td>
                        <td class="num">{{ number_format((float) $cat['amount'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    @if ($type === 'profit_loss')
        <h2>Pendapatan</h2>
        <table>
            <tbody>
                @foreach ($payload['revenue'] as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td class="num">{{ number_format((float) $row['amount'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td>Total Pendapatan</td>
                    <td class="num">{{ number_format((float) $payload['totals']['revenue'], 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <h2>Beban</h2>
        <table>
            <tbody>
                @foreach ($payload['expenses'] as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td class="num">{{ number_format((float) $row['amount'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td>Total Beban</td>
                    <td class="num">{{ number_format((float) $payload['totals']['expenses'], 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <h2>Laba Bersih: {{ number_format((float) $payload['totals']['net_income'], 0, ',', '.') }}</h2>
    @endif

    @if ($type === 'balance_sheet')
        <div class="meta">Per tanggal: {{ $payload['as_of'] }}</div>

        <h2>Aset</h2>
        <table>
            <tbody>
                @foreach ($payload['assets'] as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td class="num">{{ number_format((float) $row['amount'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td>Total Aset</td>
                    <td class="num">{{ number_format((float) $payload['totals']['assets'], 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <h2>Kewajiban</h2>
        <table>
            <tbody>
                @forelse ($payload['liabilities'] as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td class="num">{{ number_format((float) $row['amount'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2">—</td></tr>
                @endforelse
                <tr class="total">
                    <td>Total Kewajiban</td>
                    <td class="num">{{ number_format((float) $payload['totals']['liabilities'], 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <h2>Ekuitas</h2>
        <table>
            <tbody>
                @foreach ($payload['equity'] as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td class="num">{{ number_format((float) $row['amount'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td>Total Ekuitas</td>
                    <td class="num">{{ number_format((float) $payload['totals']['equity'], 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <p class="{{ $payload['totals']['is_balanced'] ? 'ok' : 'bad' }}">
            Kewajiban + Ekuitas:
            {{ number_format((float) $payload['totals']['liabilities_and_equity'], 0, ',', '.') }}
            —
            {{ $payload['totals']['is_balanced'] ? 'Seimbang' : 'Tidak seimbang (selisih '.$payload['totals']['difference'].')' }}
        </p>
    @endif

    @if ($type === 'project_profitability')
        <table>
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Client</th>
                    <th class="num">Revenue</th>
                    <th class="num">Cost</th>
                    <th class="num">Profit</th>
                    <th class="num">Margin %</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payload['projects'] as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['client_name'] ?? '—' }}</td>
                        <td class="num">{{ number_format((float) $row['revenue'], 0, ',', '.') }}</td>
                        <td class="num">{{ number_format((float) $row['cost'], 0, ',', '.') }}</td>
                        <td class="num">{{ number_format((float) $row['profit'], 0, ',', '.') }}</td>
                        <td class="num">{{ $row['margin_percent'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">Belum ada project.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    @if ($type === 'budget')
        <div class="meta">Periode: {{ $payload['period'] }}</div>
        <table>
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th class="num">Budget</th>
                    <th class="num">Actual</th>
                    <th class="num">Sisa</th>
                    <th class="num">Progress %</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payload['items'] as $row)
                    <tr>
                        <td>{{ $row['category_name'] }}</td>
                        <td class="num">{{ number_format((float) $row['budget'], 0, ',', '.') }}</td>
                        <td class="num">{{ number_format((float) $row['actual'], 0, ',', '.') }}</td>
                        <td class="num">{{ number_format((float) $row['remaining'], 0, ',', '.') }}</td>
                        <td class="num">{{ $row['progress_percent'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Belum ada budget untuk periode ini.</td></tr>
                @endforelse
                <tr class="total">
                    <td>Total</td>
                    <td class="num">{{ number_format((float) $payload['totals']['budget'], 0, ',', '.') }}</td>
                    <td class="num">{{ number_format((float) $payload['totals']['actual'], 0, ',', '.') }}</td>
                    <td class="num">{{ number_format((float) $payload['totals']['remaining'], 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @endif
</body>
</html>
