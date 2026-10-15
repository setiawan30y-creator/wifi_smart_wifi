<?php

namespace App\Services;

use App\Models\Olt;

class OltService
{
    public function testConnection(Olt $olt): array
    {
        if ($olt->protocol === 'snmp') {
            return $this->testSnmpConnection($olt);
        }

        $targetPort = $olt->protocol === 'snmp' ? $olt->snmp_port : $olt->port;
        $errorCode = 0;
        $errorMessage = '';

        $socket = @fsockopen($olt->host, (int) $targetPort, $errorCode, $errorMessage, 5);

        if (is_resource($socket)) {
            fclose($socket);

            $olt->update([
                'status' => 'online',
                'last_checked_at' => now(),
                'last_error' => null,
            ]);

            return [
                'success' => true,
                'message' => 'OLT dapat dijangkau melalui ' . strtoupper($olt->protocol) . ' port ' . $targetPort . '.',
            ];
        }

        $message = trim($errorMessage) ?: 'Tidak ada respon dari host/port OLT.';

        $olt->update([
            'status' => 'offline',
            'last_checked_at' => now(),
            'last_error' => $message,
        ]);

        return [
            'success' => false,
            'message' => $message,
        ];
    }

    private function testSnmpConnection(Olt $olt): array
    {
        if (!function_exists('snmp2_get')) {
            $message = 'Extension PHP SNMP belum aktif. Aktifkan php_snmp untuk test SNMP OLT.';
            $olt->update([
                'status' => 'offline',
                'last_checked_at' => now(),
                'last_error' => $message,
            ]);

            return [
                'success' => false,
                'message' => $message,
            ];
        }

        if (blank($olt->snmp_community)) {
            $message = 'SNMP community belum diisi.';
            $olt->update([
                'status' => 'offline',
                'last_checked_at' => now(),
                'last_error' => $message,
            ]);

            return [
                'success' => false,
                'message' => $message,
            ];
        }

        $result = @snmp2_get($olt->host . ':' . $olt->snmp_port, $olt->snmp_community, '1.3.6.1.2.1.1.1.0', 5000000, 1);

        if ($result !== false) {
            $olt->update([
                'status' => 'online',
                'last_checked_at' => now(),
                'last_error' => null,
            ]);

            return [
                'success' => true,
                'message' => 'OLT dapat dijangkau melalui SNMP.',
            ];
        }

        $message = 'OLT tidak merespon SNMP. Periksa IP, community, ACL, dan port UDP 161.';
        $olt->update([
            'status' => 'offline',
            'last_checked_at' => now(),
            'last_error' => $message,
        ]);

        return [
            'success' => false,
            'message' => $message,
        ];
    }
}
