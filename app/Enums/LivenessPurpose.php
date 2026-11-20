<?php

declare(strict_types=1);

namespace App\Enums;

enum LivenessPurpose: string
{
    case Enroll = 'enroll';
    case Identify = 'identify';
    case Delete = 'delete';
}
