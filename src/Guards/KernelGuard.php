<?php

namespace DatPM\LaravelAuthQueue\Guards;

use Exception;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;

class KernelGuard implements Guard
{
    use GuardHelpers;

    public function user()
    {
        return $this->user;
    }

    public function validate(array $credentials = [])
    {
        throw new Exception('Invalid calls in KernelGuard');
    }
}
