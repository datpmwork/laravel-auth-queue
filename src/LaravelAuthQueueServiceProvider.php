<?php

namespace DatPM\LaravelAuthQueue;

use DatPM\LaravelAuthQueue\Guards\KernelGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class LaravelAuthQueueServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Skip booting in non-console mode
        if (! $this->app->runningInConsole()) {
            return;
        }

        // Register new AuthGuard for Kernel Application
        Auth::extend('kernel', function () {
            return new KernelGuard;
        });

        // Add a new KernelGuard to support authenticated in CLI
        $defaultGuard = config('auth.defaults.guard');

        config(['auth.guards.kernel' => [
            'driver' => 'kernel',
            'provider' => config("auth.guards.{$defaultGuard}.provider"),
        ]]);
    }
}
