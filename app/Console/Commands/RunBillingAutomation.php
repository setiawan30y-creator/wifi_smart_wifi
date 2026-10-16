<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Billing\BillingAutomation;
use Illuminate\Console\Command;

/**
 * Dijalankan scheduler setiap menit (routes/console.php).
 * Logika ada di App\Services\Billing\BillingAutomation.
 */
class RunBillingAutomation extends Command
{
    protected $signature = 'billing:automation
        {--force : Jalankan generate tanpa menunggu jam otomatis}
        {--ignore-window : Abaikan rentang H- jatuh tempo untuk test/manual}
        {--tenant= : Jalankan hanya untuk tenant tertentu}';

    protected $description = 'Generate tagihan otomatis dan kirim pengingat WA jatuh tempo.';

    public function handle(BillingAutomation $automation): int
    {
        $tenants = Tenant::where('status', 'active')
            ->when($this->option('tenant'), fn ($q, $id) => $q->whereKey((int) $id))
            ->orderBy('id')
            ->get();

        if ($tenants->isEmpty()) {
            $this->warn('Tidak ada tenant aktif yang cocok untuk otomatis tagihan.');
            return self::SUCCESS;
        }

        $automation->withOutput(fn (string $line) => $this->line($line));

        foreach ($tenants as $tenant) {
            try {
                $automation->run($tenant, now(), (bool) $this->option('force'), (bool) $this->option('ignore-window'));
            } catch (\Throwable $e) {
                // Satu tenant bermasalah tidak boleh menghentikan tenant lain.
                report($e);
                $this->error("Tenant {$tenant->id}: gagal - {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
