<?php
declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case cash = 'cash';
    case card = 'card';
    case online = 'online';
}
