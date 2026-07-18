<?php
declare(strict_types=1);

namespace App\Traits;

use App\Scopes\GymScope;
use App\Services\Tenant\GymContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait GymScoped
{
    public static function bootGymScoped(): void
    {
        static::addGlobalScope(new GymScope());

        static::creating(function (Model $model): void {
            if (Auth::check()) {
                $model->gym_id = app(GymContext::class)->gymId();
            }
        });

        static::saving(function (Model $model): void {
            if (Auth::check() && $model->isDirty('gym_id')) {
                $model->gym_id = app(GymContext::class)->gymId();
            }
        });
    }
}
