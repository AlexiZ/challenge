<?php

namespace App\Enum;

enum BonusChallengeEnum: string
{
    case LeParrain = 'le_parrain';
    case PanierGarni = 'panier_garni';
    case ClasseAPlach = 'classe_a_plach';
    case PourLePlaisir = 'pour_le_plaisir';
    case FamilleNombreuse = 'famille_nombreuse';
    case TshirtMouille = 'tshirt_mouille';
    case EspritEquipe = 'esprit_equipe';
    case Panoramacyclette = 'panoramacyclette';

    public function label(): string
    {
        return match($this) {
            self::LeParrain => 'Le Parrain',
            self::PanierGarni => 'Panier garni',
            self::ClasseAPlach => 'Classe à plach',
            self::PourLePlaisir => 'Pour le plaisir',
            self::FamilleNombreuse => 'Famille nombreuse',
            self::TshirtMouille => 'T-shirt mouillé',
            self::EspritEquipe => 'Esprit d\'équipe',
            self::Panoramacyclette => 'Panoramacyclette',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::LeParrain => 'Vous avez convaincu quelqu\'un de venir avec vous ? Photo côte à côte !',
            self::PanierGarni => 'Un détour par le marché ou le supermarché : photo de vos courses chargées.',
            self::ClasseAPlach => 'Prouvez qu\'on peut être chic et mobile : photo en tenue habillée.',
            self::PourLePlaisir => 'Une photo qui donne envie de bouger : paysage, ambiance, moment de vie.',
            self::FamilleNombreuse => 'Toute la famille : enfants, parents, grands-parents bienvenus !',
            self::TshirtMouille => 'Il pleut ? Bravez l\'humidité et partagez vos plus beaux clichés sous la pluie.',
            self::EspritEquipe => 'Le plus grand nombre ensemble le même jour, en photo avec le groupe.',
            self::Panoramacyclette => 'Qui a la plus belle vue ? Votre plus beau panorama breton.',
        };
    }

    public function defaultPoints(): float
    {
        return match($this) {
            self::LeParrain => 5.0,
            self::PanierGarni => 3.0,
            self::ClasseAPlach => 4.0,
            self::PourLePlaisir => 3.0,
            self::FamilleNombreuse => 5.0,
            self::TshirtMouille => 3.0,
            self::EspritEquipe => 4.0,
            self::Panoramacyclette => 4.0,
        };
    }
}
