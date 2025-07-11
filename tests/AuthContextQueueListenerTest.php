<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Illuminate\Events\CallQueuedListener;
use DatPM\LaravelAuthQueue\Tests\Models\User;
use DatPM\LaravelAuthQueue\Tests\Controllers\TestController;
use DatPM\LaravelAuthQueue\Tests\Listeners\TestEventSubscriber;

beforeEach(function () {
    Schema::create('users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email');
    });

    // Register test routes
    Route::middleware('api')->group(function () {
        Route::post('/test/emit-event', [
            TestController::class,
            'emitEvent',
        ]);
    });

    Event::subscribe(TestEventSubscriber::class);
});

it('preserves auth context when Listener is dispatched', function () {
    if (version_compare(Application::VERSION, '10.0', '>=')) {
        Queue::fake()->serializeAndRestore();
    } else {
        Queue::fake();
    }

    /** @var \Mockery\Mock $loggerSpy */
    $loggerSpy = Mockery::spy('logger');
    $this->app->instance('log', $loggerSpy);

    // Arrange
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    // Act
    $response = $this->actingAs($user)->postJson('/test/emit-event');

    // Assert
    $response->assertSuccessful();

    Queue::assertPushed(CallQueuedListener::class);
});

it('preserves auth context when Listener is executed', function () {
    Queue::setDefaultDriver('sync');

    /** @var \Mockery\Mock $loggerSpy */
    $loggerSpy = Mockery::spy('logger');
    $this->app->instance('log', $loggerSpy);

    // Arrange
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    // Act
    $response = $this->actingAs($user)->postJson('/test/emit-event');

    // Assert
    $response->assertSuccessful();

    expect(DB::table('jobs')->count())->toBe(1);

    // Reset Auth to prevent reuse auth data of the above API
    auth()->guard()->forgetUser();

    $this->artisan('queue:work --once');

    // Assert logger was called with correct values
    $loggerSpy->shouldHaveReceived('info')
        ->with("Auth ID: {$user->id}")
        ->once();

    $loggerSpy->shouldHaveReceived('info')
        ->with('Auth Check: 1')
        ->once();
});

it('handles unauthenticated requests correctly', function () {
    // Arrange
    if (version_compare(Application::VERSION, '10.0', '>=')) {
        Queue::fake()->serializeAndRestore();
    } else {
        Queue::fake();
    }

    // Arrange
    User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    // Act
    $response = $this->postJson('/test/emit-event');

    // Assert
    $response->assertSuccessful();

    Queue::assertPushed(CallQueuedListener::class);
});

afterEach(function () {
    Schema::dropIfExists('users');
    DB::table('jobs')->delete();
});
