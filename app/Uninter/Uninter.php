<?php

namespace App\Uninter;

use App\Uninter\Services\UninterService;
use App\Uninter\Services\UninterServiceProof;
use Illuminate\Support\ServiceProvider;

class Uninter extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->singleton(UninterContractsInterface::class, function ($app) {
            return new UninterService();
        });
        $this->app->singleton(UninterProofContractsInterface::class, function ($app) {
            return new UninterServiceProof(new UninterService());
        });
    }
}
