<?php

namespace App\Services;

use App\Contracts\Repositories\PackageRepositoryInterface;
use App\Models\Package;
use App\Models\Router;
use Illuminate\Validation\ValidationException;

class PackageService
{
    public function __construct(
        private readonly PackageRepositoryInterface $packages
    ) {
    }

    public function create(array $data, bool $syncMikrotik = true): Package
    {
        $package = $this->packages->create($data);

        if ($syncMikrotik) {
            $this->syncCreateToMikrotik($package);
        }

        return $package;
    }

    public function update(Package $package, array $data, bool $syncMikrotik = true): Package
    {
        $oldName = $package->name;
        $package = $this->packages->update($package, $data);

        if ($syncMikrotik) {
            $this->syncUpdateToMikrotik($oldName, $package);
        }

        return $package;
    }

    public function delete(Package $package, bool $syncMikrotik = true): void
    {
        if ($package->customers()->count() > 0) {
            throw ValidationException::withMessages([
                'package' => 'Paket tidak dapat dihapus karena masih digunakan oleh pelanggan.',
            ]);
        }

        $name = $package->name;
        $this->packages->delete($package);

        if ($syncMikrotik) {
            $this->syncDeleteToMikrotik($name);
        }
    }

    public function parseSpeedToRateLimit(?string $speed): ?string
    {
        if (!$speed) {
            return null;
        }

        preg_match('/(\d+)/', $speed, $matches);
        if (!isset($matches[1])) {
            return null;
        }

        $num = $matches[1];

        if (stripos($speed, 'kb') !== false) {
            return $num . 'k/' . $num . 'k';
        }

        return $num . 'M/' . $num . 'M';
    }

    private function onlineRouter(): ?Router
    {
        return Router::active();
    }

    private function syncCreateToMikrotik(Package $package): void
    {
        $router = $this->onlineRouter();
        if (!$router) {
            return;
        }

        (new MikrotikService($router))->addPppoeProfile($package->name, $this->parseSpeedToRateLimit($package->speed));
    }

    private function syncUpdateToMikrotik(string $oldName, Package $package): void
    {
        $router = $this->onlineRouter();
        if (!$router) {
            return;
        }

        (new MikrotikService($router))->updatePppoeProfile($oldName, $package->name, $this->parseSpeedToRateLimit($package->speed));
    }

    private function syncDeleteToMikrotik(string $name): void
    {
        $router = $this->onlineRouter();
        if (!$router) {
            return;
        }

        (new MikrotikService($router))->deletePppoeProfile($name);
    }
}
