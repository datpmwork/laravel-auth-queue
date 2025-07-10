<?php

namespace DatPM\LaravelAuthQueue\Traits;

use DatPM\LaravelAuthQueue\Middlewares\RestoreAuthenticatedContextMiddleware;

trait WasAuthenticated
{
    /**
     * The job should go through AuthenticatedMiddleware
     */
    public function middleware()
    {
        return [new RestoreAuthenticatedContextMiddleware];
    }
}
