<?php

namespace App\Enum;

enum BonusPhotoStatusEnum: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::Pending => 'En attente',
            self::Approved => 'Publié',
            self::Rejected => 'Rejeté',
        };
    }
}
