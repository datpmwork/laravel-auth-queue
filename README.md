# Preserve the authenticated user context when dispatching Laravel queued jobs.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/datpmwork/laravel-auth-queue.svg?style=flat-square)](https://packagist.org/packages/datpmwork/laravel-auth-queue)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/datpmwork/laravel-auth-queue/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/datpmwork/laravel-auth-queue/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/datpmwork/laravel-auth-queue/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/datpmwork/laravel-auth-queue/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/datpmwork/laravel-auth-queue.svg?style=flat-square)](https://packagist.org/packages/datpmwork/laravel-auth-queue)

This package preserves the authenticated user context when dispatching Laravel queued jobs, notifications, or event
listeners. 

It allows you to seamlessly access the authenticated user who originally dispatched the job through Laravel's
auth() manager when the job is being handled. 

This is particularly useful when you need to maintain user context across
asynchronous operations.

## Support us

You can support this project via [GitHub Sponsors](https://github.com/sponsors/datpmwork).

## Installation

You can install the package via composer:

```bash
composer require datpmwork/laravel-auth-queue
```

## Usage

Add `WasAuthenticated` trait to any `Job`, `Notification`, `Listener` which need to access `auth` data when the Job was dispatched

### Example Job
```php
class SampleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, WasAuthenticated;

    public function handle()
    {
        # auth()->user() was the authenticated user who dispatched this job
        logger()->info('Auth ID: '. auth()->id());
    }
}
```

### Example Notification
```php
class SampleNotification extends Notification implements ShouldQueue
{
    use Queueable, WasAuthenticated;

    public function via(): array
    {
        return ['database'];
    }

    public function toDatabase(): array
    {
        # auth()->user() was the authenticated user who triggered this notification
        return [auth()->id()];
    }
}
```

### Example Subscriber
```php
class SampleSubscriber implements ShouldQueue
{
    use Queueable, WasAuthenticated;

    public function subscribe(Dispatcher $dispatcher)
    {
        $dispatcher->listen('eloquent.updated: ' . User::class, [self::class, 'onUserUpdated']);
    }

    public function onUserUpdated(User $user)
    {
        # auth()->user() was the authenticated user who triggered this event
        logger()->info('Auth ID: '. auth()->id());
    }
}
```

## Testing

```bash
./vendor/bin/pest
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [datpmwork](https://github.com/datpmwork)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
