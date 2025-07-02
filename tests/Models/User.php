<?php

namespace DatPM\LaravelAuthQueue\Tests\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\LaravelPackageTools\Concerns\Package\HasRoutes;

class User extends Authenticatable
{
    use HasFactory, HasRoutes, Notifiable;

    protected $fillable = ['name', 'email'];

    public $timestamps = false;
}
