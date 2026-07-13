<?php

namespace App\Enum;

enum PerceivedBenefitEnum: string
{
    case Health = 'health';
    case Ecology = 'ecology';
    case Economy = 'economy';
    case Sport = 'sport';
    case Other = 'other';

    public function label(): string
    {
        return match($this) {
            self::Health => 'Santé',
            self::Ecology => 'Écologie',
            self::Economy => 'Économies',
            self::Sport => 'Sport / plaisir',
            self::Other => 'Autre',
        };
    }
}
