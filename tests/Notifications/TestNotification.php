<?php

namespace DatPM\LaravelAuthQueue\Tests\Notifications;

use DatPM\LaravelAuthQueue\Traits\WasAuthenticated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Notifications\Notification;

class TestNotification extends Notification implements ShouldQueue
{
    use Queueable, WasAuthenticated;

    public function via(): array
    {
        return ['database'];
    }

    public function toDatabase(): array
    {
        return [auth()->id()];
    }
}
