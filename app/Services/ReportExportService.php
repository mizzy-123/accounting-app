<?php

namespace App\Services;

use App\Models\Entity;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    public function __construct(private ReportService $reports) {}

    public function downloadPdf(Entity $entity, string $type, array $filters): Response
    {
        $payload = $this->buildPayload($entity, $type, $filters);
        $title = $this->titleFor($type);

        $pdf = Pdf::loadView('reports.pdf', [
            'entity' => $entity,
            'type' => $type,
            'title' => $title,
            'payload' => $payload,
            'generatedAt' => now()->timezone(config('app.timezone'))->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');

        $filename = $this->filename($entity, $type, 'pdf');

        return $pdf->download($filename);
    }

    public function downloadExcel(Entity $entity, string $type, array $filters): StreamedResponse
    {
        $payload = $this->buildPayload($entity, $type, $filters);
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($this->titleFor($type), 0, 31));

        $this->writeExcel($sheet, $entity, $type, $payload);

        $filename = $this->filename($entity, $type, 'xlsx');

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  array{start?: string, end?: string, as_of?: string, period?: string}  $filters
     * @return array<string, mixed>
     */
    public function buildPayload(Entity $entity, string $type, array $filters): array
    {
        return match ($type) {
            'cash_flow' => $this->reports->cashFlow(
                $entity,
                Carbon::parse($filters['start']),
                Carbon::parse($filters['end']),
            ),
            'profit_loss' => $this->reports->profitAndLoss(
                $entity,
                Carbon::parse($filters['start']),
                Carbon::parse($filters['end']),
            ),
            'balance_sheet' => $this->reports->balanceSheet(
                $entity,
                Carbon::parse($filters['as_of']),
            ),
            'project_profitability' => [
                'period' => [
                    'start' => $filters['start'] ?? null,
                    'end' => $filters['end'] ?? null,
                ],
                'projects' => $this->reports->projectProfitability(
                    $entity,
                    isset($filters['start']) ? Carbon::parse($filters['start']) : null,
                    isset($filters['end']) ? Carbon::parse($filters['end']) : null,
                ),
            ],
            'budget' => $this->reports->budgetVsActual(
                $entity,
                $filters['period'],
            ),
            default => abort(404, 'Jenis laporan tidak dikenal.'),
        };
    }

    private function titleFor(string $type): string
    {
        return match ($type) {
            'cash_flow' => 'Laporan Cash Flow',
            'profit_loss' => 'Laporan Laba Rugi',
            'balance_sheet' => 'Neraca (Balance Sheet)',
            'project_profitability' => 'Profitabilitas Project',
            'budget' => 'Budget vs Actual',
            default => 'Laporan',
        };
    }

    private function filename(Entity $entity, string $type, string $ext): string
    {
        $slug = str($entity->name)->slug('_')->toString();

        return sprintf('%s_%s_%s.%s', $slug, $type, now()->format('Ymd_His'), $ext);
    }

    private function writeExcel(Worksheet $sheet, Entity $entity, string $type, array $payload): void
    {
        $row = 1;
        $sheet->setCellValue("A{$row}", $this->titleFor($type));
        $row++;
        $sheet->setCellValue("A{$row}", $entity->name);
        $row += 2;

        match ($type) {
            'cash_flow' => $this->writeCashFlow($sheet, $payload, $row),
            'profit_loss' => $this->writeProfitLoss($sheet, $payload, $row),
            'balance_sheet' => $this->writeBalanceSheet($sheet, $payload, $row),
            'project_profitability' => $this->writeProjects($sheet, $payload, $row),
            'budget' => $this->writeBudget($sheet, $payload, $row),
            default => null,
        };
    }

    private function writeCashFlow(Worksheet $sheet, array $payload, int $row): void
    {
        $sheet->setCellValue("A{$row}", 'Bulan');
        $sheet->setCellValue("B{$row}", 'Pemasukan');
        $sheet->setCellValue("C{$row}", 'Pengeluaran');
        $sheet->setCellValue("D{$row}", 'Net');
        $row++;

        foreach ($payload['monthly'] as $month) {
            $sheet->setCellValue("A{$row}", $month['label']);
            $sheet->setCellValue("B{$row}", (float) $month['income']);
            $sheet->setCellValue("C{$row}", (float) $month['expense']);
            $sheet->setCellValue("D{$row}", (float) $month['net']);
            $row++;
        }

        $row++;
        $sheet->setCellValue("A{$row}", 'Total');
        $sheet->setCellValue("B{$row}", (float) $payload['totals']['income']);
        $sheet->setCellValue("C{$row}", (float) $payload['totals']['expense']);
        $sheet->setCellValue("D{$row}", (float) $payload['totals']['net']);
        $row += 2;

        $sheet->setCellValue("A{$row}", 'Kategori');
        $sheet->setCellValue("B{$row}", 'Tipe');
        $sheet->setCellValue("C{$row}", 'Jumlah');
        $row++;

        foreach ($payload['categories'] as $cat) {
            $sheet->setCellValue("A{$row}", $cat['name']);
            $sheet->setCellValue("B{$row}", $cat['type']);
            $sheet->setCellValue("C{$row}", (float) $cat['amount']);
            $row++;
        }
    }

    private function writeProfitLoss(Worksheet $sheet, array $payload, int $row): void
    {
        $sheet->setCellValue("A{$row}", 'Pendapatan');
        $row++;
        foreach ($payload['revenue'] as $item) {
            $sheet->setCellValue("A{$row}", $item['name']);
            $sheet->setCellValue("B{$row}", (float) $item['amount']);
            $row++;
        }
        $sheet->setCellValue("A{$row}", 'Total Pendapatan');
        $sheet->setCellValue("B{$row}", (float) $payload['totals']['revenue']);
        $row += 2;

        $sheet->setCellValue("A{$row}", 'Beban');
        $row++;
        foreach ($payload['expenses'] as $item) {
            $sheet->setCellValue("A{$row}", $item['name']);
            $sheet->setCellValue("B{$row}", (float) $item['amount']);
            $row++;
        }
        $sheet->setCellValue("A{$row}", 'Total Beban');
        $sheet->setCellValue("B{$row}", (float) $payload['totals']['expenses']);
        $row += 2;

        $sheet->setCellValue("A{$row}", 'Laba Bersih');
        $sheet->setCellValue("B{$row}", (float) $payload['totals']['net_income']);
    }

    private function writeBalanceSheet(Worksheet $sheet, array $payload, int $row): void
    {
        $sheet->setCellValue("A{$row}", 'Per tanggal');
        $sheet->setCellValue("B{$row}", $payload['as_of']);
        $row += 2;

        $sheet->setCellValue("A{$row}", 'Aset');
        $row++;
        foreach ($payload['assets'] as $item) {
            $sheet->setCellValue("A{$row}", $item['name']);
            $sheet->setCellValue("B{$row}", (float) $item['amount']);
            $row++;
        }
        $sheet->setCellValue("A{$row}", 'Total Aset');
        $sheet->setCellValue("B{$row}", (float) $payload['totals']['assets']);
        $row += 2;

        $sheet->setCellValue("A{$row}", 'Kewajiban');
        $row++;
        foreach ($payload['liabilities'] as $item) {
            $sheet->setCellValue("A{$row}", $item['name']);
            $sheet->setCellValue("B{$row}", (float) $item['amount']);
            $row++;
        }
        $sheet->setCellValue("A{$row}", 'Total Kewajiban');
        $sheet->setCellValue("B{$row}", (float) $payload['totals']['liabilities']);
        $row += 2;

        $sheet->setCellValue("A{$row}", 'Ekuitas');
        $row++;
        foreach ($payload['equity'] as $item) {
            $sheet->setCellValue("A{$row}", $item['name']);
            $sheet->setCellValue("B{$row}", (float) $item['amount']);
            $row++;
        }
        $sheet->setCellValue("A{$row}", 'Total Ekuitas');
        $sheet->setCellValue("B{$row}", (float) $payload['totals']['equity']);
        $row += 2;

        $sheet->setCellValue("A{$row}", 'Total Kewajiban + Ekuitas');
        $sheet->setCellValue("B{$row}", (float) $payload['totals']['liabilities_and_equity']);
        $row++;
        $sheet->setCellValue("A{$row}", 'Seimbang');
        $sheet->setCellValue("B{$row}", $payload['totals']['is_balanced'] ? 'Ya' : 'Tidak');
    }

    private function writeProjects(Worksheet $sheet, array $payload, int $row): void
    {
        $sheet->setCellValue("A{$row}", 'Project');
        $sheet->setCellValue("B{$row}", 'Client');
        $sheet->setCellValue("C{$row}", 'Revenue');
        $sheet->setCellValue("D{$row}", 'Cost');
        $sheet->setCellValue("E{$row}", 'Profit');
        $sheet->setCellValue("F{$row}", 'Margin %');
        $row++;

        foreach ($payload['projects'] as $project) {
            $sheet->setCellValue("A{$row}", $project['name']);
            $sheet->setCellValue("B{$row}", $project['client_name'] ?? '');
            $sheet->setCellValue("C{$row}", (float) $project['revenue']);
            $sheet->setCellValue("D{$row}", (float) $project['cost']);
            $sheet->setCellValue("E{$row}", (float) $project['profit']);
            $sheet->setCellValue("F{$row}", $project['margin_percent'] ?? '');
            $row++;
        }
    }

    private function writeBudget(Worksheet $sheet, array $payload, int $row): void
    {
        $sheet->setCellValue("A{$row}", 'Periode');
        $sheet->setCellValue("B{$row}", $payload['period']);
        $row += 2;

        $sheet->setCellValue("A{$row}", 'Kategori');
        $sheet->setCellValue("B{$row}", 'Budget');
        $sheet->setCellValue("C{$row}", 'Actual');
        $sheet->setCellValue("D{$row}", 'Sisa');
        $sheet->setCellValue("E{$row}", 'Progress %');
        $row++;

        foreach ($payload['items'] as $item) {
            $sheet->setCellValue("A{$row}", $item['category_name']);
            $sheet->setCellValue("B{$row}", (float) $item['budget']);
            $sheet->setCellValue("C{$row}", (float) $item['actual']);
            $sheet->setCellValue("D{$row}", (float) $item['remaining']);
            $sheet->setCellValue("E{$row}", (float) $item['progress_percent']);
            $row++;
        }

        $row++;
        $sheet->setCellValue("A{$row}", 'Total');
        $sheet->setCellValue("B{$row}", (float) $payload['totals']['budget']);
        $sheet->setCellValue("C{$row}", (float) $payload['totals']['actual']);
        $sheet->setCellValue("D{$row}", (float) $payload['totals']['remaining']);
    }
}
