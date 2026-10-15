<?php

namespace App\Services;

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

        $data = $this->extractCoordinates($data);

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
        $data = $this->extractCoordinates($data);

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
     * Extract latitude and longitude from maps_link
     */
    protected function extractCoordinates(array $data): array
    {
        if (!empty($data['maps_link'])) {
            $link = $data['maps_link'];
            $lat = null;
            $lng = null;

            $patterns = [
                '/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/',
                '/(?:q|query|center|ll)=(-?\d+(?:\.\d+)?)(?:%2C|,\+|,|\s+)(-?\d+(?:\.\d+)?)/',
                '/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/',
                '/(?:place|dir|search)\/.*?\/?(-?\d+(?:\.\d+)?)(?:%2C|,\+|,|\s+)(-?\d+(?:\.\d+)?)/',
                '/^(-?\d+(?:\.\d+)?)(?:\s*,\s*|\s+)(-?\d+(?:\.\d+)?)$/',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, trim($link), $matches)) {
                    $lat = $matches[1];
                    $lng = $matches[2];
                    break;
                }
            }

            if ($lat && $lng) {
                $data['latitude'] = $lat;
                $data['longitude'] = $lng;
            }
        }
        return $data;
    }
}
