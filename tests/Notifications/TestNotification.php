<?php

namespace DatPM\LaravelAuthQueue\Tests\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use DatPM\LaravelAuthQueue\Traits\WasAuthenticated;

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
