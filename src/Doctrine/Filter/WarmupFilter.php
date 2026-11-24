<?php

namespace App\Doctrine\Filter;

use App\Entity\BonusPhoto;
use App\Entity\Trip;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * Compteurs remis à 0 au démarrage officiel : une fois l'édition commencée,
 * les trajets et défis photo saisis pendant la période de chauffe (avant startDate)
 * sont exclus de toutes les requêtes. Avant le démarrage, ils restent visibles.
 */
class WarmupFilter extends SQLFilter
{
    private const DATE_FIELDS = [
        Trip::class => 'tripDate',
        BonusPhoto::class => 'submittedAt',
    ];

    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        $field = self::DATE_FIELDS[$targetEntity->getName()] ?? null;
        if ($field === null) {
            return '';
        }

        $date = $targetTableAlias . '.' . $targetEntity->getColumnName($field);
        $cityEditionId = $targetTableAlias . '.' . $targetEntity->getSingleAssociationJoinColumnName('cityEdition');

        return sprintf(
            'NOT EXISTS (SELECT 1 FROM city_edition warmup_ce INNER JOIN edition warmup_e ON warmup_e.id = warmup_ce.edition_id'
            . ' WHERE warmup_ce.id = %s AND warmup_e.start_date <= CURRENT_DATE AND %s < warmup_e.start_date)',
            $cityEditionId,
            $date,
        );
    }
}
