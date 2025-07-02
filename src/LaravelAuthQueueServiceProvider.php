<?php

namespace DatPM\LaravelAuthQueue;

use Illuminate\Support\Facades\Auth;
use Spatie\LaravelPackageTools\Package;
use DatPM\LaravelAuthQueue\Guards\KernelGuard;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelAuthQueueServiceProvider extends PackageServiceProvider
{
    public function boot()
    {
        # Skip booting in non-console mode
        if (!$this->app->runningInConsole()) {
            return;
        }

        # Register new AuthGuard for Kernel Application
        Auth::extend('kernel', function () {
            return new KernelGuard();
        });

        # Add a new KernelGuard to support authenticated in CLI
        $defaultGuard = config('auth.defaults.guard');

        config(['auth.guards.kernel' => [
            'driver' => 'kernel',
            'provider' => config("auth.guards.{$defaultGuard}.provider"),
        ]]);
    }

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name('laravel-queueable-auth-context');
    }
}
