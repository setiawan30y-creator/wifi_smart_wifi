<?php

namespace App\Services;

use App\Support\MapsCoordinates;
use App\Models\Customer;

class CustomerService
{
    protected FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Store a new customer.
     */
    public function storeCustomer(array $data, array $files = []): Customer
    {
        $data['status'] = 'active';

        $data = MapsCoordinates::apply($data);

        $photoFields = ['foto_ktp', 'foto_rumah', 'foto_perangkat', 'foto_odp'];
        foreach ($photoFields as $field) {
            if (isset($files[$field])) {
                $data[$field] = $this->fileUploadService->upload($files[$field], 'uploads/customers', $field);
            }
        }

        return Customer::create($data);
    }

    /**
     * Update an existing customer.
     */
    public function updateCustomer(Customer $customer, array $data, array $files = []): Customer
    {
        $data = MapsCoordinates::apply($data);

        $photoFields = ['foto_ktp', 'foto_rumah', 'foto_perangkat', 'foto_odp'];
        foreach ($photoFields as $field) {
            if (isset($files[$field])) {
                $data[$field] = $this->fileUploadService->replace($files[$field], $customer->$field, 'uploads/customers', $field);
            }
        }

        $customer->update($data);
        return $customer;
    }


    /**
     * Ubah status pelanggan (aktif / nonaktif / isolir / berhenti) dan catat riwayatnya.
     */
    public function changeStatus(Customer $customer, string $newStatus, string $action, ?string $reason = null, ?\Illuminate\Support\Carbon $changedAt = null): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($customer, $newStatus, $action, $reason, $changedAt) {
            $oldStatus = $customer->status;

            if ($oldStatus === $newStatus) {
                return;
            }

            $customer->update(['status' => $newStatus]);

            $customer->statusHistories()->create([
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'action' => $action,
                'reason' => $reason,
                'changed_by' => auth()->id(),
                'changed_at' => $changedAt ?? now(),
            ]);
        });
    }
}
