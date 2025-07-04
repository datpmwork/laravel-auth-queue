<?php

use Illuminate\Foundation\Application;
use DatPM\LaravelAuthQueue\Middlewares\RestoreAuthenticatedContextMiddleware;
use DatPM\LaravelAuthQueue\Tests\Controllers\TestController;
use DatPM\LaravelAuthQueue\Tests\Jobs\TestWasAuthenticatedJob;
use DatPM\LaravelAuthQueue\Tests\Jobs\TestWasNotAuthenticatedJob;
use DatPM\LaravelAuthQueue\Tests\Models\User;
use Illuminate\Support\Facades\DB;
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
        Route::post('/test/dispatch-job', [
            TestController::class,
            'dispatchJob',
        ]);
    });
});

it('preserves auth context when Job is dispatched', function () {
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
    $response = $this->actingAs($user)->postJson('/test/dispatch-job');

    // Assert
    $response->assertSuccessful();

    Queue::assertPushed(TestWasAuthenticatedJob::class, function (TestWasAuthenticatedJob $job) use ($user) {
        return collect($job->middleware)->filter(function ($middleware) use ($user) {
            return get_class($middleware) === RestoreAuthenticatedContextMiddleware::class &&
                $middleware->getAuthUser()->getAuthIdentifier() === $user->getKey();
        });
    });
    Queue::assertPushed(TestWasNotAuthenticatedJob::class, 1);
});

it('preserves auth context when Job is executed', function () {
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
    $response = $this->actingAs($user)->postJson('/test/dispatch-job');

    // Assert
    $response->assertSuccessful();

    expect(DB::table('jobs')->count())->toBe(2);

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

    $this->artisan('queue:work --once');

    // Assert logger was called with correct values
    $loggerSpy->shouldHaveReceived('info')
        ->with('Auth ID: ')
        ->once();

    $loggerSpy->shouldHaveReceived('info')
        ->with('Auth Check: ')
        ->once();
});

it('handles unauthenticated requests correctly', function () {
    // Arrange
    if (version_compare(Application::VERSION, '10.0', '>=')) {
        Queue::fake()->serializeAndRestore();
    } else {
        Queue::fake();
    }

    // Act
    $response = $this->postJson('/test/dispatch-job');

    // Assert
    $response->assertSuccessful();

    Queue::assertPushed(TestWasAuthenticatedJob::class, function (TestWasAuthenticatedJob $job) {
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
