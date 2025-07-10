<?php

namespace DatPM\LaravelAuthQueue\Tests\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use DatPM\LaravelAuthQueue\Traits\WasAuthenticated;

class TestWasAuthenticatedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, WasAuthenticated;

    public function handle()
    {
        logger()->info('Auth ID: '.auth()->id());
        logger()->info('Auth Check: '.auth()->check());
    }
}
