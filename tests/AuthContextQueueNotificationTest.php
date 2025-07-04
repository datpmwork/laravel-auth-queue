<?php

use Illuminate\Foundation\Application;
use DatPM\LaravelAuthQueue\Middlewares\RestoreAuthenticatedContextMiddleware;
use DatPM\LaravelAuthQueue\Tests\Controllers\TestController;
use DatPM\LaravelAuthQueue\Tests\Models\User;
use DatPM\LaravelAuthQueue\Tests\Notifications\TestNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
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
        Route::post('/test/send-notification', [
            TestController::class,
            'sendNotification',
        ]);
    });
});

it('preserves auth context when Notification is dispatched', function () {
    if (version_compare(Application::VERSION, '10.0', '>=')) {
        Notification::fake()->serializeAndRestore();
    } else {
        Notification::fake();
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
    $response = $this->actingAs($user)->postJson('/test/send-notification');

    // Assert
    $response->assertSuccessful();

    Notification::assertCount(1);

    Notification::assertSentTo($user, TestNotification::class, function (TestNotification $job) use ($user) {
        return collect($job->middleware)->filter(function ($middleware) use ($user) {
            return get_class($middleware) === RestoreAuthenticatedContextMiddleware::class &&
                $middleware->getAuthUser()->getAuthIdentifier() === $user->getKey();
        });
    });
});

it('preserves auth context when Notification is executed', function () {
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
    $response = $this->actingAs($user)->postJson('/test/send-notification');

    // Assert
    $response->assertSuccessful();

    // Reset Auth to prevent reuse auth data of the above API
    auth()->guard()->forgetUser();

    $this->artisan('queue:work --once');

    expect(DB::table('notifications')->count())->toBe(1);

    expect(DB::table('notifications')->value('data'))->toBe("[{$user->getAuthIdentifier()}]");
});

it('handles unauthenticated requests correctly', function () {
    // Arrange
    if (version_compare(Application::VERSION, '10.0', '>=')) {
        Notification::fake()->serializeAndRestore();
    } else {
        Notification::fake();
    }

    // Arrange
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    // Act
    $response = $this->postJson('/test/send-notification');

    // Assert
    $response->assertSuccessful();

    Notification::assertSentTo($user, TestNotification::class, function (TestNotification $job) {
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
