<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Exception;

class WalletService
{
    /**
     * Credit (Top up) customer's wallet.
     * 
     * @param Customer $customer
     * @param float $amount
     * @param string $description
     * @param string|null $referenceId
     * @return WalletTransaction
     */
    public function credit(Customer $customer, $amount, $description = 'Topup', $referenceId = null)
    {
        if ($amount <= 0) {
            throw new Exception("Amount must be greater than zero.");
        }

        return DB::transaction(function () use ($customer, $amount, $description, $referenceId) {
            $customer->increment('balance', $amount);
            $customer->refresh();

            return WalletTransaction::create([
                'customer_id' => $customer->id,
                'type' => 'credit',
                'amount' => $amount,
                'balance_after' => $customer->balance,
                'reference_id' => $referenceId,
                'description' => $description,
            ]);
        });
    }

    /**
     * Debit (Deduct) customer's wallet for a purchase/payment.
     * 
     * @param Customer $customer
     * @param float $amount
     * @param string $description
     * @param string|null $referenceId
     * @return WalletTransaction
     * @throws Exception if balance is insufficient
     */
    public function debit(Customer $customer, $amount, $description = 'Payment', $referenceId = null)
    {
        if ($amount <= 0) {
            throw new Exception("Amount must be greater than zero.");
        }

        return DB::transaction(function () use ($customer, $amount, $description, $referenceId) {
            // Lock the row for update to prevent race conditions (double spending)
            $lockedCustomer = Customer::where('id', $customer->id)->lockForUpdate()->first();

            if ($lockedCustomer->balance < $amount) {
                throw new Exception("Saldo tidak mencukupi. Sisa saldo: " . number_format($lockedCustomer->balance, 0, ',', '.'));
            }

            $lockedCustomer->decrement('balance', $amount);
            $lockedCustomer->refresh();

            return WalletTransaction::create([
                'customer_id' => $lockedCustomer->id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_after' => $lockedCustomer->balance,
                'reference_id' => $referenceId,
                'description' => $description,
            ]);
        });
    }
}
