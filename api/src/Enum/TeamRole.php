<?php

declare(strict_types=1);

namespace App\Enum;

enum TeamRole: string
{
    case LEAD = 'LEAD';
    case MEMBER = 'MEMBER';
}
