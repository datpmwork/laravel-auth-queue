<?php

namespace DatPM\LaravelAuthQueue\Middlewares;

use Illuminate\Support\Facades\Auth;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Auth\Authenticatable;

class RestoreAuthenticatedContextMiddleware
{
    use SerializesModels;

    public function handle($command, callable $next)
    {
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
