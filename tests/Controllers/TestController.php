<?php

namespace DatPM\LaravelAuthQueue\Tests\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use DatPM\LaravelAuthQueue\Tests\Models\User;
use DatPM\LaravelAuthQueue\Tests\Jobs\TestWasAuthenticatedJob;
use DatPM\LaravelAuthQueue\Tests\Notifications\TestNotification;
use DatPM\LaravelAuthQueue\Tests\Jobs\TestWasNotAuthenticatedJob;

class TestController extends Controller
{
    public function dispatchJob(): JsonResponse
    {
        TestWasAuthenticatedJob::dispatch();
        TestWasNotAuthenticatedJob::dispatch();

        return response()->json(['status' => 'dispatched job']);
    }

    public function sendNotification(): JsonResponse
    {
        $notification = new TestNotification();
        User::query()->first()->notify($notification);

        return response()->json(['status' => 'dispatched notification']);
    }

    public function emitEvent(): JsonResponse
    {
        User::query()->first()->update([
            'name' => 'Name Updated',
        ]);

        return response()->json(['status' => 'emitted event']);
    }
}
