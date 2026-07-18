<?php
declare(strict_types=1);

namespace App\Enums;

enum MemberStatus: string
{
    case active = 'active';
    case inactive = 'inactive';
}
