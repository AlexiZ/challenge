<?php

namespace App\Controller;

use App\Repository\CityEditionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(CityEditionRepository $cityEditionRepository): Response
    {
        $now = new \DateTime();

        // One entry per city (most recent edition), grouped into past/current/upcoming tabs
        $buckets = ['past' => [], 'current' => [], 'upcoming' => []];
        foreach ($cityEditionRepository->findLatestPerCity() as $ce) {
            $edition = $ce->getEdition();
            $key = match (true) {
                $ce->isActive()                 => 'current',
                $edition->getStartDate() > $now => 'upcoming',
                default                          => 'past',
            };
            $buckets[$key][] = ['cityEdition' => $ce, 'status' => $key];
        }

        $cityTabs = [
            [
                'key' => 'past',
                'label' => 'Éditions passées',
                'emptyMessage' => 'Aucune édition passée pour le moment.',
                'cities' => $buckets['past'],
                'active' => false,
            ],
            [
                'key' => 'current',
                'label' => 'Éditions en cours',
                'emptyMessage' => 'Aucune édition en cours pour le moment.',
                'cities' => $buckets['current'],
                'active' => true,
            ],
            [
                'key' => 'upcoming',
                'label' => 'Éditions à venir',
                'emptyMessage' => 'Aucune édition à venir pour le moment.',
                'cities' => $buckets['upcoming'],
                'active' => false,
            ],
        ];

        // Active editions only — for the inter-city ranking
        $activeCityEditions = $cityEditionRepository->findAllActiveEditions();
        $interCityRanking = [];
        foreach ($activeCityEditions as $ce) {
            $totalKm = 0.0;
            foreach ($ce->getTrips() as $trip) {
                $totalKm += $trip->getDistanceKm();
            }
            $interCityRanking[] = [
                'city'           => $ce->getCity(),
                'cityEdition'    => $ce,
                'totalKm'        => $totalKm,
                'participantCount' => $ce->getParticipants()->count(),
            ];
        }
        usort($interCityRanking, fn ($a, $b) => $b['totalKm'] <=> $a['totalKm']);

        return $this->render('home/index.html.twig', [
            'cityTabs'         => $cityTabs,
            'interCityRanking' => $interCityRanking,
        ]);
    }
}
