<?php

namespace DatPM\LaravelAuthQueue\Middlewares;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

class RestoreAuthenticatedContextMiddleware
{
    protected $authUser;

    public function __construct($authUser)
    {
        $this->authUser = $authUser;
    }

    public function handle($command, callable $next)
    {
        Auth::shouldUse('kernel');

        $guard = auth();

        if (! empty($this->authUser)) {
            $guard->setUser($this->authUser);
        }

        $response = $next($command);

        $guard->forgetUser();

        return $response;
    }

    /**
     * @return Authenticatable|null
     */
    public function getAuthUser()
    {
        return $this->authUser;
    }
}
