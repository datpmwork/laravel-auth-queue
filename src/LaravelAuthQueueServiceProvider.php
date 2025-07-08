<?php

namespace DatPM\LaravelAuthQueue;

use Illuminate\Queue\Queue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use DatPM\LaravelAuthQueue\Guards\KernelGuard;
use Illuminate\Contracts\Database\ModelIdentifier;
use DatPM\LaravelAuthQueue\Traits\WasAuthenticated;

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

        Queue::createPayloadUsing(function ($connectionName, $queue, $payload) {
            # Skip attaching authUser when the job does not use WasAuthenticated Trait
            if (!in_array(WasAuthenticated::class, class_uses_recursive($payload['displayName']))) {
                return [];
            }

            $user = auth()->user();
            if (empty($user)) {
                return [];
            }

            $userPayload = new ModelIdentifier(get_class($user), $user->getQueueableId(), [], $user->getQueueableConnection());

            return [
                'authUser' => serialize($userPayload),
            ];
        });
    }
}
