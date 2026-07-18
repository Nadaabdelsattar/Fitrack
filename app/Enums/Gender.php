<?php
declare(strict_types=1);

namespace App\Enums;

enum Gender: string
{
    case male = 'male';
    case female = 'female';
    case other = 'other';
}
