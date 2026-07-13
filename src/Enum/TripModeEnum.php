<?php

namespace App\Enum;

enum TripModeEnum: string
{
    case Bike = 'bike';
    case Walk = 'walk';

    public function label(): string
    {
        return match($this) {
            self::Bike => 'Vélo',
            self::Walk => 'Marche',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Bike => '🚲',
            self::Walk => '🚶',
        };
    }

    public function faIcon(): string
    {
        return match($this) {
            self::Bike => 'fas fa-bicycle',
            self::Walk => 'fas fa-person-walking',
        };
    }
}
