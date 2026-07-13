<?php

namespace App\Enum;

enum GenderEnum: string
{
    case Male = 'male';
    case Female = 'female';
    case Other = 'other';
    case PreferNotToSay = 'prefer_not_to_say';

    public function label(): string
    {
        return match($this) {
            self::Male => 'Homme',
            self::Female => 'Femme',
            self::Other => 'Autre',
            self::PreferNotToSay => 'Préfère ne pas répondre',
        };
    }
}
