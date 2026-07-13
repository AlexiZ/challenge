<?php

namespace App\Enum;

enum MainBarrierEnum: string
{
    case Distance = 'distance';
    case Weather = 'weather';
    case Hills = 'hills';
    case Safety = 'safety';
    case Equipment = 'equipment';
    case Other = 'other';

    public function label(): string
    {
        return match($this) {
            self::Distance => 'Distance trop longue',
            self::Weather => 'Météo',
            self::Hills => 'Dénivelé',
            self::Safety => 'Sécurité routière',
            self::Equipment => 'Manque d\'équipement',
            self::Other => 'Autre',
        };
    }
}
