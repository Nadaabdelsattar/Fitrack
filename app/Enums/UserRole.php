<?php
declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case admin = 'admin';
    case staff = 'staff';
    case trainer = 'trainer';
}
