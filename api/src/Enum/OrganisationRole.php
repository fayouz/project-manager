<?php

declare(strict_types=1);

namespace App\Enum;

enum OrganisationRole: string
{
    case OWNER = 'OWNER';
    case ADMIN = 'ADMIN';
    case MEMBER = 'MEMBER';
}
