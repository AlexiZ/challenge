<?php

namespace App\Enum;

enum BikeTypeEnum: string
{
    case Classic = 'classic';
    case Vtc = 'vtc';
    case Vae = 'vae';
    case Cargo = 'cargo';
    case Other = 'other';

    public function label(): string
    {
        return match($this) {
            self::Classic => 'Vélo classique',
            self::Vtc => 'VTC',
            self::Vae => 'VAE (électrique)',
            self::Cargo => 'Vélo cargo',
            self::Other => 'Autre',
        };
    }
}
