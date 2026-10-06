<?php

namespace App\Console\Commands;

use App\Models\FixedAsset;
use App\Services\DepreciationService;
use Illuminate\Console\Command;

class ProcessDepreciation extends Command
{
    protected $signature = 'accounting:process-depreciation {--through= : Periode YYYY-MM}';

    protected $description = 'Catat penyusutan garis lurus untuk semua aset aktif sampai periode berjalan';

    public function handle(DepreciationService $depreciation): int
    {
        $through = $this->option('through') ?: now()->format('Y-m');
        $postedTotal = 0;
        $skipped = 0;

        FixedAsset::query()
            ->where('status', 'active')
            ->with('entity')
            ->orderBy('acquisition_date')
            ->each(function (FixedAsset $asset) use ($depreciation, $through, &$postedTotal, &$skipped): void {
                $owner = $asset->entity?->users()
                    ->wherePivot('role', 'owner')
                    ->first();

                if ($owner === null) {
                    $skipped++;
                    $this->warn("Aset {$asset->id} dilewati: entity tidak punya owner.");

                    return;
                }

                $posted = $depreciation->postThrough($asset, $owner, $through);
                $postedTotal += $posted;

                if ($posted > 0) {
                    $this->line("{$asset->name}: {$posted} periode tercatat.");
                }
            });

        $this->info("Selesai. {$postedTotal} periode dicatat".($skipped > 0 ? ", {$skipped} aset dilewati." : '.'));

        return self::SUCCESS;
    }
}
