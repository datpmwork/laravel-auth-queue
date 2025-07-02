<?php

namespace DatPM\LaravelAuthQueue\Tests\Listeners;

use DatPM\LaravelAuthQueue\Tests\Models\User;
use DatPM\LaravelAuthQueue\Traits\WasAuthenticated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;

class TestEventSubscriber implements ShouldQueue
{
    use Queueable, WasAuthenticated;

    public function subscribe(Dispatcher $dispatcher)
    {
        $dispatcher->listen('eloquent.updated: '.User::class, [self::class, 'onUserUpdated']);
    }

    public function onUserUpdated(User $user)
    {
        logger()->info('Auth ID: '.auth()->id());
        logger()->info('Auth Check: '.auth()->check());
    }
}
