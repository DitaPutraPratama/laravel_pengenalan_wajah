<?php

declare(strict_types=1);

namespace App\Enums;

enum ChallengeType: string
{
    case Blink = 'blink';
    case TurnRight = 'turn_right';
    case TurnLeft = 'turn_left';
}
