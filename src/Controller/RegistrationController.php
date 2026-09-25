<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\CityRepository;
use App\Service\UserRegistrar;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]
    public function register(
        Request $request,
        CityRepository $cityRepository,
        UserRegistrar $userRegistrar,
        RateLimiterFactory $registrationLimiter,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $prefillCity = null;
        $citySlug = $request->query->get('citySlug');
        if (is_string($citySlug)) {
            $prefillCity = $cityRepository->findOneBy(['slug' => $citySlug]);
        }

        $registrationForm = $this->createForm(RegistrationFormType::class, (new User())->setCity($prefillCity));
        $registrationForm->handleRequest($request);

        if ($registrationForm->isSubmitted() && $registrationForm->isValid()) {
            if (!$registrationLimiter->create($request->getClientIp())->consume()->isAccepted()) {
                $registrationForm->addError(new FormError('Trop de tentatives. Merci de réessayer plus tard.'));
            } else {
                /** @var User $user */
                $user = $registrationForm->getData();
                foreach ($registrationForm->get('teams')->getData() as $team) {
                    $team->addMember($user);
                }
                $userRegistrar->register($user, $registrationForm->get('plainPassword')->getData());

                $this->addFlash('success', $user->getPassword() === null
                    ? 'Votre compte a été créé. Consultez vos emails pour recevoir votre lien de connexion.'
                    : 'Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter.');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $registrationForm,
        ]);
    }
}
