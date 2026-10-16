<?php

namespace App\Services\Customers;

use App\Models\Customer;

/**
 * Export daftar pelanggan ke Excel 2003 XML (.xls) — bisa dibuka Excel/LibreOffice
 * dan bisa di-import kembali lewat CustomerImporter.
 */
class CustomerExporter
{

    /**
     * Export all customers to Excel XML (.xls) — no external library needed.
     */
    public function xml(): string
    {
        $customers = Customer::with(['package', 'odp', 'statusHistories'])->orderBy('name')->get();

        // Build SpreadsheetML (Excel 2003 XML) — opens natively in Excel & LibreOffice
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= '  xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xml .= '  xmlns:x="urn:schemas-microsoft-com:office:excel">' . "\n";
        $xml .= '<Worksheet ss:Name="Pelanggan"><Table>' . "\n";

        // Header row
        $headers = [
            'ID', 'Nama', 'Email', 'No. Telepon', 'Alamat',
            'Paket', 'ODP', 'Status', 'Tgl Tagih',
            'Jenis Tagihan', 'Catatan Gratis', 'Tgl Pasang', 'PPPoE Username', 'Status Rumah',
            'Pemilik Rumah', 'Jalur', 'Perangkat',
            'Tanggal Isolir', 'Tanggal Berhenti', 'Tanggal Aktif Kembali',
            'Tanggal Update Status Terakhir', 'Catatan Status Terakhir',
        ];
        $xml .= '<Row>' . "\n";
        foreach ($headers as $h) {
            $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($h, ENT_XML1) . '</Data></Cell>' . "\n";
        }
        $xml .= '</Row>' . "\n";

        // Data rows
        foreach ($customers as $c) {
            $statusLabels = [
                'active' => 'Aktif',
                'inactive' => 'Nonaktif',
                'isolated' => 'Isolir',
                'stopped' => 'Berhenti',
            ];
            $isolatedAt = $c->statusHistories->firstWhere('action', 'isolir')?->changed_at;
            $stoppedAt = $c->statusHistories->firstWhere('action', 'berhenti')?->changed_at;
            $reactivatedAt = $c->statusHistories->firstWhere('action', 'aktif_kembali')?->changed_at;
            $latestStatusHistory = $c->statusHistories->first();

            $xml .= '<Row>' . "\n";
            $cols = [
                $c->id,
                $c->name,
                $c->email ?? '',
                $c->phone,
                $c->address,
                $c->package ? $c->package->name : '',
                $c->odp ? $c->odp->name : '',
                $statusLabels[$c->status] ?? $c->status,
                $c->billing_day,
                $c->billingTypeLabel(),
                $c->billing_note ?? '',
                $c->tanggal_pasang ?? '',
                $c->pppoe_username ?? '',
                $c->status_rumah ?? '',
                $c->pemilik_rumah ?? '',
                $c->jalur ?? '',
                $c->perangkat ?? '',
                $isolatedAt ? $isolatedAt->format('Y-m-d H:i:s') : '',
                $stoppedAt ? $stoppedAt->format('Y-m-d H:i:s') : '',
                $reactivatedAt ? $reactivatedAt->format('Y-m-d H:i:s') : '',
                $latestStatusHistory ? $latestStatusHistory->changed_at->format('Y-m-d H:i:s') : '',
                $latestStatusHistory ? ($latestStatusHistory->reason ?? '') : '',
            ];
            foreach ($cols as $col) {
                $type = is_numeric($col) && !preg_match('/^0\d/', $col) ? 'Number' : 'String';
                $xml .= '<Cell><Data ss:Type="' . $type . '">' . htmlspecialchars((string) $col, ENT_XML1) . '</Data></Cell>' . "\n";
            }
            $xml .= '</Row>' . "\n";
        }

        $xml .= '</Table></Worksheet></Workbook>';

        return $xml;
    }
}
