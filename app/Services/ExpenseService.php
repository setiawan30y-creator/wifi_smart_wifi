<?php

namespace App\Services;

use App\Models\DeviceStock;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function create(array $data): Expense
    {
        return DB::transaction(function () use ($data) {
            $data = $this->resolveNewDevice($data);
            $expense = Expense::create($data);
            $this->applyStockIncrement($expense);

            return $expense->load('deviceStock');
        });
    }

    public function update(Expense $expense, array $data): Expense
    {
        return DB::transaction(function () use ($expense, $data) {
            $this->revertStockIncrement($expense);
            $data = $this->resolveNewDevice($data);
            $expense->update($data);
            $this->applyStockIncrement($expense);

            return $expense->refresh()->load('deviceStock');
        });
    }

    public function delete(Expense $expense): void
    {
        DB::transaction(function () use ($expense) {
            $this->revertStockIncrement($expense);
            $expense->delete();
        });
    }

    private function resolveNewDevice(array $data): array
    {
        if (($data['category'] ?? null) === 'Pembelian Perangkat' && ($data['device_stock_id'] ?? null) === 'NEW_DEVICE') {
            $device = DeviceStock::create([
                'brand' => $data['new_device_brand'],
                'name' => $data['new_device_name'],
                'type' => $data['new_device_type'],
                'stock' => 0,
                'unit_price' => 0,
            ]);
            $data['device_stock_id'] = $device->id;
        }

        unset($data['new_device_brand'], $data['new_device_name'], $data['new_device_type']);

        return $data;
    }

    private function applyStockIncrement(Expense $expense): void
    {
        if ($expense->category !== 'Pembelian Perangkat' || !$expense->device_stock_id || !$expense->quantity) {
            return;
        }

        $device = DeviceStock::find($expense->device_stock_id);
        if (!$device) {
            return;
        }

        $device->increment('stock', $expense->quantity);
        $device->update(['unit_price' => (int) ($expense->amount / $expense->quantity)]);
    }

    private function revertStockIncrement(Expense $expense): void
    {
        if ($expense->category !== 'Pembelian Perangkat' || !$expense->device_stock_id || !$expense->quantity) {
            return;
        }

        $device = DeviceStock::find($expense->device_stock_id);
        if ($device) {
            $device->update(['stock' => max(0, $device->stock - $expense->quantity)]);
        }
    }
}
