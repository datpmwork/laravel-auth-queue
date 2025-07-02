<?php

use DatPM\LaravelAuthQueue\Middlewares\RestoreAuthenticatedContextMiddleware;
use DatPM\LaravelAuthQueue\Tests\Controllers\TestController;
use DatPM\LaravelAuthQueue\Tests\Listeners\TestEventSubscriber;
use DatPM\LaravelAuthQueue\Tests\Models\User;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

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
    Queue::fake()->serializeAndRestore();

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

    Queue::assertCount(1);

    Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $job) use ($user) {
        return collect($job->middleware)->filter(function ($middleware) use ($user) {
            return get_class($middleware) === RestoreAuthenticatedContextMiddleware::class &&
                $middleware->getAuthUser()->getAuthIdentifier() === $user->getKey();
        });
    });
});

it('preserves auth context when Listener is executed', function () {
    Queue::setDefaultDriver('database');

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
    Queue::fake()->serializeAndRestore();

    // Arrange
    User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    // Act
    $response = $this->postJson('/test/emit-event');

    // Assert
    $response->assertSuccessful();

    Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $job) {
        return collect($job->middleware)->filter(function ($middleware) {
            return get_class($middleware) === RestoreAuthenticatedContextMiddleware::class &&
                empty($middleware->getAuthUser());
        });
    });
});

afterEach(function () {
    Schema::dropIfExists('users');
    DB::table('jobs')->delete();
});
