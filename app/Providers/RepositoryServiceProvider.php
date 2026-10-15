<?php

namespace App\Providers;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Repositories\InvoiceRepositoryInterface;
use App\Contracts\Repositories\OdpRepositoryInterface;
use App\Contracts\Repositories\PackageRepositoryInterface;
use App\Repositories\Eloquent\CustomerRepository;
use App\Repositories\Eloquent\InvoiceRepository;
use App\Repositories\Eloquent\OdpRepository;
use App\Repositories\Eloquent\PackageRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);
        $this->app->bind(InvoiceRepositoryInterface::class, InvoiceRepository::class);
        $this->app->bind(OdpRepositoryInterface::class, OdpRepository::class);
        $this->app->bind(PackageRepositoryInterface::class, PackageRepository::class);
    }
}
