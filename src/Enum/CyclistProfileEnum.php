<?php

namespace App\Enum;

enum CyclistProfileEnum: string
{
    case Novice = 'novice';
    case Occasional = 'occasional';
    case Regular = 'regular';

    public function label(): string
    {
        return match($this) {
            self::Novice => 'Novice',
            self::Occasional => 'Occasionnel',
            self::Regular => 'Régulier',
        };
    }
}
