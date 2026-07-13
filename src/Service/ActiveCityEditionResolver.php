<?php

namespace App\Service;

use App\Entity\CityEdition;
use App\Repository\CityEditionRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ActiveCityEditionResolver
{
    public function __construct(
        private readonly CityEditionRepository $cityEditionRepository,
        private readonly RequestStack $requestStack,
    ) {}

    /**
     * Returns the active CityEdition for the given city slug.
     * Falls back to the most recent edition if none is currently active.
     * Throws 404 if no edition exists at all for this city.
     */
    public function resolve(string $citySlug): CityEdition
    {
        $cityEdition = $this->cityEditionRepository->findActiveByCitySlug($citySlug)
            ?? $this->cityEditionRepository->findLatestByCitySlug($citySlug);

        if ($cityEdition === null) {
            throw new NotFoundHttpException(sprintf(
                'Aucune édition trouvée pour la ville "%s".',
                $citySlug
            ));
        }

        return $cityEdition;
    }

    public function resolveFromRequest(): ?CityEdition
    {
        $request = $this->requestStack->getCurrentRequest();
        $citySlug = $request?->attributes->get('citySlug');

        if ($citySlug === null) {
            return null;
        }

        return $this->cityEditionRepository->findActiveByCitySlug($citySlug)
            ?? $this->cityEditionRepository->findLatestByCitySlug($citySlug);
    }
}
