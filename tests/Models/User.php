<?php

namespace DatPM\LaravelAuthQueue\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\LaravelPackageTools\Concerns\Package\HasRoutes;

class User extends Authenticatable
{
    use HasFactory, HasRoutes, Notifiable;

    protected $fillable = ['name', 'email'];

    public $timestamps = false;
}
