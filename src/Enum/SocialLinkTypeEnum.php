<?php

namespace App\Enum;

enum SocialLinkTypeEnum: string
{
    case Facebook = 'facebook';
    case Instagram = 'instagram';
    case Twitter = 'twitter';
    case Website = 'website';

    public function label(): string
    {
        return match($this) {
            self::Facebook => 'Facebook',
            self::Instagram => 'Instagram',
            self::Twitter => 'Twitter / X',
            self::Website => 'Site web',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Facebook => '📘',
            self::Instagram => '📷',
            self::Twitter => '🐦',
            self::Website => '🌐',
        };
    }

    public function faIcon(): string
    {
        return match($this) {
            self::Facebook  => 'fab fa-facebook',
            self::Instagram => 'fab fa-instagram',
            self::Twitter   => 'fab fa-x-twitter',
            self::Website   => 'fas fa-globe',
        };
    }
}
