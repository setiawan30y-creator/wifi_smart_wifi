<?php

namespace App\Services\Customers;

use App\Models\Customer;
use App\Models\Odp;
use App\Models\Package;
use App\Support\SpreadsheetReader;

/**
 * Import pelanggan dari file .xlsx / .xls (hasil export sistem) / .csv / .txt.
 * Pelanggan dicocokkan lewat nomor telepon: yang sudah ada diperbarui, yang belum ada dibuat.
 */
class CustomerImporter
{
    public const EXTENSIONS = ['csv', 'txt', 'xls', 'xlsx'];

    /**
     * @return array{rows: int, imported: int, skipped: int, errors: array<int, string>}
     */
    public function import(string $path, string $extension): array
    {
        $rows = SpreadsheetReader::read($path, $extension);

        if (empty($rows)) {
            return ['rows' => 0, 'imported' => 0, 'skipped' => 0, 'errors' => []];
        }

        // Skip header row
        $header = array_shift($rows);
        $headerMap = $this->customerImportHeaderMap($header);
        $useCompactFallback = !$this->hasRecognizedCustomerImportHeader($headerMap);

        if ($useCompactFallback) {
            array_unshift($rows, $header);
        }

        $fallback = $useCompactFallback
            ? [
                'name' => 0,
                'email' => null,
                'phone' => 1,
                'address' => 2,
                'package' => 3,
                'odp' => 4,
                'status' => 5,
                'billing_day' => 6,
                'billing_type' => null,
                'billing_mode' => null,
                'billing_note' => null,
                'tanggal_pasang' => 7,
                'pppoe_username' => 8,
                'status_rumah' => 9,
                'pemilik_rumah' => 10,
                'jalur' => 11,
                'perangkat' => 12,
            ]
            : [
                'name' => 1,
                'email' => 2,
                'phone' => 3,
                'address' => 4,
                'package' => 5,
                'odp' => 6,
                'status' => 7,
                'billing_day' => 8,
                'billing_type' => 9,
                'billing_mode' => null,
                'billing_note' => 10,
                'tanggal_pasang' => 11,
                'pppoe_username' => 12,
                'status_rumah' => 13,
                'pemilik_rumah' => 14,
                'jalur' => 15,
                'perangkat' => 16,
            ];

        $packages = Package::pluck('id', 'name')->toArray();
        $odps     = Odp::pluck('id', 'name')->toArray();

        $importedCount = 0;
        $skippedCount  = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            $row = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $row);
            // Expect: ID, Nama, Email, No.Telepon, Alamat, Paket, ODP, Status, TglTagih, TglPasang, PPPoE, StatusRumah, PemilikRumah, Jalur, Perangkat
            if ($this->isEmptyImportRow($row)) {
                $skippedCount++;
                continue;
            }

            $name    = $this->rowValue($row, $headerMap, ['nama', 'name', 'namapelanggan', 'pelanggan'], $fallback['name']);
            $phone   = $this->normalizePhoneNumber($this->rowValue($row, $headerMap, ['notelepon', 'telepon', 'phone', 'hp', 'nohp', 'kontak'], $fallback['phone']));
            $address = $this->rowValue($row, $headerMap, ['alamat', 'address', 'alamatrumah'], $fallback['address']);

            if (empty($name) || empty($phone) || empty($address)) {
                $errors[] = "Baris " . ($i + 2) . ": Nama, No. Telepon, atau Alamat kosong, baris dilewati.";
                $skippedCount++;
                continue;
            }

            $packageName = $this->rowValue($row, $headerMap, ['paket', 'package', 'paketlayanan'], $fallback['package']);
            $odpName     = $this->rowValue($row, $headerMap, ['odp', 'odpterhubung'], $fallback['odp']);
            $statusInput = strtolower($this->rowValue($row, $headerMap, ['status'], $fallback['status']));
            $statusMap = [
                'aktif' => 'active',
                'active' => 'active',
                'nonaktif' => 'inactive',
                'inactive' => 'inactive',
                'isolir' => 'isolated',
                'isolated' => 'isolated',
                'berhenti' => 'stopped',
                'stopped' => 'stopped',
            ];
            $status = $statusMap[$statusInput] ?? 'active';
            $billingDay  = (int) ($this->rowValue($row, $headerMap, ['tgltagih', 'tanggaltagih', 'billingday'], $fallback['billing_day']) ?: 1);
            $billingDay  = max(1, min(28, $billingDay));
            $billingType = $this->normalizeBillingType($this->rowValue($row, $headerMap, ['jenistagihan', 'billingtype', 'tipetagihan', 'gratis'], $fallback['billing_type']));
            $billingMode = $this->normalizeBillingMode($this->rowValue($row, $headerMap, ['billingmode', 'sistempembayaran', 'modepembayaran'], $fallback['billing_mode']));
            $billingNote = $this->rowValue($row, $headerMap, ['catatangratis', 'billingnote', 'keterangangratis'], $fallback['billing_note']) ?: null;

            $packageId = isset($packages[$packageName]) ? $packages[$packageName] : null;
            $odpId     = isset($odps[$odpName])     ? $odps[$odpName]     : null;

            if (!$packageId) {
                // Try to find by partial match
                foreach ($packages as $pName => $pId) {
                    if (stripos($pName, $packageName) !== false || stripos($packageName, $pName) !== false) {
                        $packageId = $pId;
                        break;
                    }
                }
            }

            if (!$packageId) {
                $defaultPackage = Package::orderBy('id')->first();
                if ($defaultPackage) {
                    $packageId = $defaultPackage->id;
                    $errors[] = "Baris " . ($i + 2) . ": Paket '$packageName' tidak ditemukan, otomatis memakai '{$defaultPackage->name}'.";
                } else {
                    $errors[] = "Baris " . ($i + 2) . ": Paket '$packageName' tidak ditemukan dan belum ada paket di database, baris dilewati.";
                    $skippedCount++;
                    continue;
                }
            }

            try {
                Customer::updateOrCreate(
                    ['phone' => $phone],
                    [
                    'package_id'    => $packageId,
                    'odp_id'        => $odpId,
                    'name'          => $name,
                    'email'         => $this->normalizeEmail($this->rowValue($row, $headerMap, ['email'], $fallback['email'])),
                    'phone'         => $phone,
                    'address'       => $address,
                    'status'        => $status,
                    'billing_day'   => $billingDay,
                    'billing_type'  => $billingType,
                    'billing_mode'  => $billingMode,
                    'billing_note'  => $billingNote,
                    'tanggal_pasang'=> $this->normalizeImportDate($this->rowValue($row, $headerMap, ['tglpasang', 'tanggalpasang'], $fallback['tanggal_pasang'])),
                    'pppoe_username'=> $this->rowValue($row, $headerMap, ['pppoeusername', 'usernamepppoe', 'pppoe'], $fallback['pppoe_username']) ?: null,
                    'status_rumah'  => $this->normalizeHomeStatus($this->rowValue($row, $headerMap, ['statusrumah'], $fallback['status_rumah'])),
                    'pemilik_rumah' => $this->rowValue($row, $headerMap, ['pemilikrumah'], $fallback['pemilik_rumah']) ?: null,
                    'jalur'         => $this->normalizeConnectionType($this->rowValue($row, $headerMap, ['jalur'], $fallback['jalur'])),
                    'perangkat'     => $this->rowValue($row, $headerMap, ['perangkat'], $fallback['perangkat']) ?: null,
                    ]
                );
            } catch (\Throwable $e) {
                $errors[] = "Baris " . ($i + 2) . ": gagal disimpan (" . $e->getMessage() . ").";
                $skippedCount++;
                continue;
            }

            $importedCount++;
        }

        return ['rows' => count($rows), 'imported' => $importedCount, 'skipped' => $skippedCount, 'errors' => $errors];
    }

    private function customerImportHeaderMap(array $header): array
    {
        $map = [];

        foreach ($header as $index => $label) {
            $key = $this->normalizeImportHeader((string) $label);
            if ($key !== '') {
                $map[$key] = $index;
            }
        }

        return $map;
    }

    private function normalizeImportHeader(string $label): string
    {
        $label = strtolower(trim($label));
        $label = str_replace(['.', '_', '-', '/', '\\', ' '], '', $label);

        return preg_replace('/[^a-z0-9]/', '', $label) ?: '';
    }

    private function hasRecognizedCustomerImportHeader(array $headerMap): bool
    {
        $knownHeaders = [
            'id',
            'nama',
            'name',
            'namapelanggan',
            'pelanggan',
            'notelepon',
            'telepon',
            'phone',
            'hp',
            'nohp',
            'alamat',
            'address',
            'paket',
            'package',
            'odp',
            'status',
        ];

        foreach ($knownHeaders as $header) {
            if (array_key_exists($header, $headerMap)) {
                return true;
            }
        }

        return false;
    }

    private function rowValue(array $row, array $headerMap, array $keys, ?int $fallbackIndex): string
    {
        foreach ($keys as $key) {
            $normalizedKey = $this->normalizeImportHeader($key);
            if (array_key_exists($normalizedKey, $headerMap)) {
                return trim((string) ($row[$headerMap[$normalizedKey]] ?? ''));
            }
        }

        if ($fallbackIndex === null) {
            return '';
        }

        return trim((string) ($row[$fallbackIndex] ?? ''));
    }

    private function isEmptyImportRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizePhoneNumber(string $phone): string
    {
        $phone = trim($phone);
        $phone = preg_replace('/\.0$/', '', $phone);
        $phone = preg_replace('/[^\d+]/', '', $phone);

        if (str_starts_with($phone, '62')) {
            return '0' . substr($phone, 2);
        }

        return $phone;
    }

    private function normalizeHomeStatus(string $value): string
    {
        $value = strtolower(trim($value));

        return in_array($value, ['ngontrak', 'kontrak', 'sewa'], true) ? 'ngontrak' : 'milik_sendiri';
    }

    private function normalizeConnectionType(string $value): ?string
    {
        $value = strtoupper(trim($value));

        return in_array($value, ['FO', 'LAN'], true) ? $value : null;
    }

    private function normalizeBillingType(string $value): string
    {
        $value = $this->normalizeImportHeader($value);

        $map = [
            '' => 'paid',
            'berbayar' => 'paid',
            'paid' => 'paid',
            'normal' => 'paid',
            'regular' => 'paid',
            'gratis' => 'other_free',
            'free' => 'other_free',
            'lainnya' => 'other_free',
            'otherfree' => 'other_free',
            'admin' => 'admin',
            'gratisadmin' => 'admin',
            'sosial' => 'social',
            'social' => 'social',
            'gratissosial' => 'social',
            'gratissocial' => 'social',
            'keluarga' => 'family',
            'family' => 'family',
            'gratiskeluarga' => 'family',
            'anakyatim' => 'orphan',
            'yatim' => 'orphan',
            'orphan' => 'orphan',
            'gratisanakyatim' => 'orphan',
            'rt' => 'rt',
            'jatahrt' => 'rt',
            'gratisrt' => 'rt',
        ];

        return $map[$value] ?? 'paid';
    }

    private function normalizeBillingMode(?string $value): string
    {
        $value = $this->normalizeImportHeader((string) $value);

        return in_array($value, ['prabayar', 'prepaid', 'pra bayar'], true)
            ? Customer::MODE_PREPAID
            : Customer::MODE_POSTPAID;
    }

    private function normalizeEmail(string $email): ?string
    {
        $email = trim($email);

        if ($email === '') {
            return null;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function normalizeImportDate(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $serial = (int) $value;
            if ($serial > 0) {
                return \Carbon\Carbon::create(1899, 12, 30)->addDays($serial)->format('Y-m-d');
            }
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d', 'd M Y', 'd F Y'];
        foreach ($formats as $format) {
            try {
                $date = \Carbon\Carbon::createFromFormat($format, $value);
                if ($date) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable) {
                //
            }
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
