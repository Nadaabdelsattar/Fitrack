<?php
declare(strict_types=1);

namespace App\Enums;

enum SubscriptionStatus: string
{
    case active = 'active';
    case expired = 'expired';
    case paused = 'paused';
}
