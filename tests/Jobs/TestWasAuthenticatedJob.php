<?php

namespace DatPM\LaravelAuthQueue\Tests\Jobs;

use DatPM\LaravelAuthQueue\Traits\WasAuthenticated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class TestWasAuthenticatedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, WasAuthenticated;

    public function handle()
    {
        logger()->info('Auth ID: '.auth()->id());
        logger()->info('Auth Check: '.auth()->check());
    }
}
