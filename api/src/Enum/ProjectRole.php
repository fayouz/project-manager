<?php

declare(strict_types=1);

namespace App\Enum;

enum ProjectRole: string
{
    case MAINTAINER = 'MAINTAINER';
    case DEVELOPER = 'DEVELOPER';
    case GUEST = 'GUEST';
}
