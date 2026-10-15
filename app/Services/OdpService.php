<?php

namespace App\Services;

use App\Contracts\Repositories\OdpRepositoryInterface;
use App\Models\Odp;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class OdpService
{
    public function __construct(
        private readonly OdpRepositoryInterface $odps
    ) {
    }

    public function create(array $data, array $files = []): Odp
    {
        [$odpData, $devicesToSave] = $this->prepareData($data, $files);

        $odp = $this->odps->create($odpData);

        foreach ($devicesToSave as $deviceData) {
            $odp->devices()->create($deviceData);
        }

        return $odp->load(['parentOdp', 'devices'])->loadCount('customers');
    }

    public function update(Odp $odp, array $data, array $files = []): Odp
    {
        [$odpData, $devicesToSave] = $this->prepareData($data, $files, $odp);

        $odp = $this->odps->update($odp, $odpData);
        $odp->devices()->delete();

        foreach ($devicesToSave as $deviceData) {
            $odp->devices()->create($deviceData);
        }

        return $odp->load(['parentOdp', 'devices'])->loadCount('customers');
    }

    public function delete(Odp $odp): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $key = 'photo_' . $i;
            if ($odp->$key && Storage::disk('public')->exists($odp->$key)) {
                Storage::disk('public')->delete($odp->$key);
            }
        }

        $this->odps->delete($odp);
    }

    private function prepareData(array $data, array $files = [], ?Odp $existingOdp = null): array
    {
        $devices = $data['devices'] ?? [];
        unset($data['devices']);

        $data = $this->extractCoordinates($data);
        $data = $this->handlePhotos($data, $files, $existingOdp);

        $totalFo = 0;
        $totalLan = 0;
        $devicesToSave = [];

        foreach ($devices as $deviceInput) {
            $spec = $deviceInput['device_spec'];
            if ($spec === 'custom') {
                $spec = $deviceInput['device_spec_custom'] ?? 'kustom';
            }

            [$foPorts, $lanPorts] = $this->parsePorts($deviceInput['device_type'], $spec);

            $totalFo += $foPorts;
            $totalLan += $lanPorts;

            $devicesToSave[] = [
                'device_type' => $deviceInput['device_type'],
                'device_spec' => $spec,
                'fo_ports' => $foPorts,
                'lan_ports' => $lanPorts,
            ];
        }

        $data['fo_ports'] = $totalFo;
        $data['lan_ports'] = $totalLan;

        return [$data, $devicesToSave];
    }

    private function handlePhotos(array $data, array $files, ?Odp $existingOdp = null): array
    {
        for ($i = 1; $i <= 4; $i++) {
            $key = 'photo_' . $i;
            if (!isset($files[$key]) || !$files[$key] instanceof UploadedFile) {
                continue;
            }

            if ($existingOdp && $existingOdp->$key && Storage::disk('public')->exists($existingOdp->$key)) {
                Storage::disk('public')->delete($existingOdp->$key);
            }

            $data[$key] = $files[$key]->store('odps', 'public');
        }

        return $data;
    }

    public function parsePorts(string $deviceType, string $deviceSpec): array
    {
        $deviceSpec = strtolower(trim($deviceSpec));
        $foPorts = 0;
        $lanPorts = 0;

        if (preg_match('/^1\/(\d+)$/', $deviceSpec, $matches)) {
            return [(int) $matches[1], 0];
        }

        if (preg_match('/(\d+)\s*fo/', $deviceSpec, $matches)) {
            $foPorts = (int) $matches[1];
        }

        if (preg_match('/(\d+)\s*lan/', $deviceSpec, $matches)) {
            $lanPorts = (int) $matches[1];
        }

        if ($foPorts === 0 && $lanPorts === 0 && is_numeric($deviceSpec)) {
            if ($deviceType === 'FO') {
                $foPorts = (int) $deviceSpec;
            } else {
                $lanPorts = (int) $deviceSpec;
            }
        }

        return [$foPorts, $lanPorts];
    }

    public function extractCoordinates(array $data): array
    {
        if (empty($data['maps_link'])) {
            return $data;
        }

        $link = $data['maps_link'];
        $lat = null;
        $lng = null;

        if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $link, $matches)) {
            $lat = $matches[1];
            $lng = $matches[2];
        } elseif (preg_match('/q=(-?\d+\.\d+),(-?\d+\.\d+)/', $link, $matches)) {
            $lat = $matches[1];
            $lng = $matches[2];
        } elseif (preg_match('/^(-?\d+\.\d+)(?:\s*,\s*|\s+)(-?\d+\.\d+)$/', trim($link), $matches)) {
            $lat = $matches[1];
            $lng = $matches[2];
        } elseif (preg_match('/search\/(-?\d+\.\d+)(?:%2C|,\+|,|\s+)(-?\d+\.\d+)/', $link, $matches)) {
            $lat = $matches[1];
            $lng = $matches[2];
        }

        if ($lat && $lng) {
            $data['latitude'] = $lat;
            $data['longitude'] = $lng;
        }

        return $data;
    }
}
