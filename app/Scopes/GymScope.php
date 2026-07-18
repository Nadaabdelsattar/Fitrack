<?php
declare(strict_types=1);

namespace App\Scopes;

use App\Services\Tenant\GymContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class GymScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $gymId = $this->resolveGymId();

        $builder->where($model->getTable() . '.gym_id', $gymId);
    }

    protected function resolveGymId(): int
    {
        return app(GymContext::class)->gymId();
    }
}
