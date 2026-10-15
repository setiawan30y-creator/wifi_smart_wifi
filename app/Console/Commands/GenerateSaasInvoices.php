<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

class GenerateSaasInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'saas:generate-invoices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate SaaS billing invoices for tenants whose subscription is expiring within 7 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting SaaS Invoice Generation...');
        
        $tenants = \App\Models\Tenant::where('status', 'active')
            ->whereNotNull('subscription_ends_at')
            ->whereDate('subscription_ends_at', '<=', now()->addDays(7))
            ->get();

        $count = 0;
        foreach ($tenants as $tenant) {
            // Check if there is already an unpaid invoice for this tenant
            $existingInvoice = \App\Models\SaasInvoice::where('tenant_id', $tenant->id)
                ->where('status', 'unpaid')
                ->first();
                
            if (!$existingInvoice) {
                // Generate Invoice
                // For simplicity, we assume a flat rate per plan. In a real app, this would query a SubscriptionPlan table.
                $amount = $tenant->subscription_plan === 'pro' ? 250000 : 100000;
                
                $invoice = \App\Models\SaasInvoice::create([
                    'tenant_id' => $tenant->id,
                    'invoice_number' => 'SAAS-INV-' . date('Ymd') . '-' . rand(1000, 9999),
                    'amount' => $amount,
                    'status' => 'unpaid',
                    'due_date' => $tenant->subscription_ends_at,
                ]);
                
                $this->info("Generated invoice {$invoice->invoice_number} for Tenant ID {$tenant->id}");
                $count++;
                
                // TODO: Send WhatsApp notification via WA Gateway
            }
        }
        
        // Also check if any tenant has passed the due date and lock them
        $expiredTenants = \App\Models\Tenant::where('status', 'active')
            ->whereNotNull('subscription_ends_at')
            ->whereDate('subscription_ends_at', '<', now())
            ->get();
            
        foreach ($expiredTenants as $expired) {
            $expired->update(['status' => 'inactive']);
            $this->info("Locked Tenant ID {$expired->id} due to expired subscription");
        }

        $this->info("Finished! Generated {$count} invoices.");
    }
}
