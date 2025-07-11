<?php

namespace DatPM\LaravelAuthQueue\Middlewares;

use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Auth\Authenticatable;

class RestoreAuthenticatedContextMiddleware
{
    use SerializesModels;

    public function handle($command, callable $next)
    {
        // If the job is being handled in sync, skip restore logic
        if ($command->job instanceof SyncJob) {
            return $next($command);
        }

        Auth::shouldUse('kernel');

        $guard = auth();

        $embedUserData = data_get($command->job->payload(), 'authUser');
        if ($embedUserData) {
            $embedUser = $this->restoreModel(unserialize($embedUserData));
            if ($embedUser instanceof Authenticatable) {
                $guard->setUser($embedUser);
            }
        }

        $response = $next($command);

        $guard->forgetUser();

        return $response;
    }
}
