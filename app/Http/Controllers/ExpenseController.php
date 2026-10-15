<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\DeviceStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the expenses.
     */
    public function index()
    {
        $expenses = Expense::with('deviceStock')->orderBy('date', 'desc')->orderBy('id', 'desc')->get();
        $deviceStocks = DeviceStock::orderBy('brand')->orderBy('name')->get();
        return view('expenses.index', compact('expenses', 'deviceStocks'));
    }

    /**
     * Store a newly created expense in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'title' => 'required|string|max:255',
            'category' => 'required|string|in:Pembelian Perangkat,Operasional,Gaji/Honor,Perbaikan,Lain-lain,ISP,Listrik,Perlengkapan',
            'amount' => 'required|integer|min:0',
            'date' => 'required|date',
            'device_stock_id' => 'required_if:category,Pembelian Perangkat|nullable',
            'quantity' => 'required_if:category,Pembelian Perangkat|nullable|integer|min:1',
            'description' => 'nullable|string',
            'receipt_photo' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:4096',
        ];

        if ($request->input('category') === 'Pembelian Perangkat' && $request->input('device_stock_id') === 'NEW_DEVICE') {
            $rules['new_device_brand'] = 'required|string|max:255';
            $rules['new_device_name'] = 'required|string|max:255|unique:device_stocks,name';
            $rules['new_device_type'] = 'required|string|in:FO,LAN';
            $rules['new_device_image'] = 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:4096';
        } else if ($request->input('category') === 'Pembelian Perangkat' && $request->input('device_stock_id') !== 'NEW_DEVICE') {
            $rules['device_stock_id'] = 'required|exists:device_stocks,id';
        }

        $validated = $request->validate($rules);

        try {
            $expensePhotoPath = $this->storeExpensePhoto($request, 'receipt_photo');
            if ($expensePhotoPath && Schema::hasColumn('expenses', 'receipt_photo')) {
                $validated['receipt_photo'] = $expensePhotoPath;
            } else {
                unset($validated['receipt_photo']);
            }

            DB::transaction(function () use (&$validated, $request) {
                if ($validated['category'] === 'Pembelian Perangkat' && $validated['device_stock_id'] === 'NEW_DEVICE') {
                    $newDeviceImage = $this->storeExpensePhoto($request, 'new_device_image', 'devices');
                    $newDevice = DeviceStock::create([
                        'brand' => $request->new_device_brand,
                        'name' => $request->new_device_name,
                        'type' => $request->new_device_type,
                        'image' => $newDeviceImage,
                        'stock' => 0,
                        'unit_price' => 0,
                    ]);
                    $validated['device_stock_id'] = $newDevice->id;
                }

                $expense = Expense::create($validated);

                // If it is a device purchase, increment stock and update unit price
                if ($expense->category === 'Pembelian Perangkat' && $expense->device_stock_id && $expense->quantity) {
                    $device = DeviceStock::find($expense->device_stock_id);
                    if ($device) {
                        $device->increment('stock', $expense->quantity);

                        // Update the unit price to the latest unit price (amount / quantity)
                        $latestUnitPrice = (int) ($expense->amount / $expense->quantity);
                        $device->update(['unit_price' => $latestUnitPrice]);
                    }
                }
            });
        } catch (\Throwable $exception) {
            Log::error('Failed to store expense.', ['error' => $exception->getMessage()]);

            return back()->withInput()->with('error', 'Gagal mencatat pengeluaran: ' . $exception->getMessage());
        }

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran baru berhasil dicatat.');
    }

    /**
     * Update the specified expense in storage.
     */
    public function update(Request $request, Expense $expense)
    {
        $rules = [
            'title' => 'required|string|max:255',
            'category' => 'required|string|in:Pembelian Perangkat,Operasional,Gaji/Honor,Perbaikan,Lain-lain,ISP,Listrik,Perlengkapan',
            'amount' => 'required|integer|min:0',
            'date' => 'required|date',
            'device_stock_id' => 'required_if:category,Pembelian Perangkat|nullable',
            'quantity' => 'required_if:category,Pembelian Perangkat|nullable|integer|min:1',
            'description' => 'nullable|string',
            'receipt_photo' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:4096',
            'remove_receipt_photo' => 'nullable|boolean',
        ];

        if ($request->input('category') === 'Pembelian Perangkat' && $request->input('device_stock_id') === 'NEW_DEVICE') {
            $rules['new_device_brand'] = 'required|string|max:255';
            $rules['new_device_name'] = 'required|string|max:255|unique:device_stocks,name';
            $rules['new_device_type'] = 'required|string|in:FO,LAN';
            $rules['new_device_image'] = 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:4096';
        } else if ($request->input('category') === 'Pembelian Perangkat' && $request->input('device_stock_id') !== 'NEW_DEVICE') {
            $rules['device_stock_id'] = 'required|exists:device_stocks,id';
        }

        $validated = $request->validate($rules);
        unset($validated['remove_receipt_photo']);

        try {
            if (Schema::hasColumn('expenses', 'receipt_photo') && ($request->boolean('remove_receipt_photo') || $request->hasFile('receipt_photo'))) {
                if ($expense->receipt_photo) {
                    Storage::disk('public')->delete($expense->receipt_photo);
                }
                $validated['receipt_photo'] = null;
            }

            $expensePhotoPath = $this->storeExpensePhoto($request, 'receipt_photo');
            if ($expensePhotoPath && Schema::hasColumn('expenses', 'receipt_photo')) {
                $validated['receipt_photo'] = $expensePhotoPath;
            } elseif (!Schema::hasColumn('expenses', 'receipt_photo')) {
                unset($validated['receipt_photo']);
            }

            DB::transaction(function () use ($expense, &$validated, $request) {
                // Revert old stock adjustment if previous category was device purchase
                if ($expense->category === 'Pembelian Perangkat' && $expense->device_stock_id && $expense->quantity) {
                    $oldDevice = DeviceStock::find($expense->device_stock_id);
                    if ($oldDevice) {
                        $oldDevice->update(['stock' => max(0, $oldDevice->stock - $expense->quantity)]);
                    }
                }

                if ($validated['category'] === 'Pembelian Perangkat' && $validated['device_stock_id'] === 'NEW_DEVICE') {
                    $newDeviceImage = $this->storeExpensePhoto($request, 'new_device_image', 'devices');
                    $newDevice = DeviceStock::create([
                        'brand' => $request->new_device_brand,
                        'name' => $request->new_device_name,
                        'type' => $request->new_device_type,
                        'image' => $newDeviceImage,
                        'stock' => 0,
                        'unit_price' => 0,
                    ]);
                    $validated['device_stock_id'] = $newDevice->id;
                }

                // Update expense record
                $expense->update($validated);

                // Apply new stock adjustment if new category is device purchase
                if ($expense->category === 'Pembelian Perangkat' && $expense->device_stock_id && $expense->quantity) {
                    $newDevice = DeviceStock::find($expense->device_stock_id);
                    if ($newDevice) {
                        $newDevice->increment('stock', $expense->quantity);

                        // Update latest unit price
                        $latestUnitPrice = (int) ($expense->amount / $expense->quantity);
                        $newDevice->update(['unit_price' => $latestUnitPrice]);
                    }
                }
            });
        } catch (\Throwable $exception) {
            Log::error('Failed to update expense.', [
                'expense_id' => $expense->id,
                'error' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Gagal memperbarui pengeluaran: ' . $exception->getMessage());
        }

        return redirect()->route('expenses.index')->with('success', 'Catatan pengeluaran berhasil diperbarui.');
    }

    public function receiptPhoto(Expense $expense)
    {
        abort_unless($expense->receipt_photo, 404);
        abort_unless(Storage::disk('public')->exists($expense->receipt_photo), 404);

        return Storage::disk('public')->response($expense->receipt_photo);
    }

    /**
     * Remove the specified expense from storage.
     */
    public function destroy(Expense $expense)
    {
        DB::transaction(function () use ($expense) {
            // Revert stock adjustment if it was a device purchase
            if ($expense->category === 'Pembelian Perangkat' && $expense->device_stock_id && $expense->quantity) {
                $device = DeviceStock::find($expense->device_stock_id);
                if ($device) {
                    $device->update(['stock' => max(0, $device->stock - $expense->quantity)]);
                }
            }

            if ($expense->receipt_photo) {
                Storage::disk('public')->delete($expense->receipt_photo);
            }

            $expense->delete();
        });

        return redirect()->route('expenses.index')->with('success', 'Catatan pengeluaran berhasil dihapus.');
    }

    private function storeExpensePhoto(Request $request, string $field, string $directory = 'expenses'): ?string
    {
        if (!$request->hasFile($field)) {
            return null;
        }

        $file = $request->file($field);

        if (!$file->isValid()) {
            throw new \RuntimeException('File gambar tidak valid. Silakan pilih ulang foto.');
        }

        return $file->store($directory, 'public');
    }
}
