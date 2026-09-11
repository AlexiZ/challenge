<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\QuickRegistrationFormType;
use App\Form\RegistrationFormType;
use App\Repository\CityRepository;
use App\Repository\UserRepository;
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
        UserRepository $userRepository,
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

        $quickForm = $this->createForm(QuickRegistrationFormType::class, ['city' => $prefillCity]);
        $quickForm->handleRequest($request);

        if ($quickForm->isSubmitted() && $quickForm->isValid()) {
            $email = $quickForm->get('email')->getData();

            if ($userRepository->findOneBy(['email' => $email])) {
                $quickForm->get('email')->addError(new FormError('Un compte existe déjà avec cet email.'));
            } elseif (!$registrationLimiter->create($request->getClientIp())->consume()->isAccepted()) {
                $quickForm->get('email')->addError(new FormError('Trop de tentatives. Merci de réessayer plus tard.'));
            } else {
                $userRegistrar->registerQuick($email, $quickForm->get('city')->getData());

                $this->addFlash('success', 'Votre compte a été créé. Consultez vos emails pour recevoir votre lien de connexion.');

                return $this->redirectToRoute('app_login');
            }
        }

        $registrationForm = $this->createForm(RegistrationFormType::class, (new User())->setCity($prefillCity));

        if (!$quickForm->isSubmitted()) {
            $registrationForm->handleRequest($request);

            if ($registrationForm->isSubmitted() && $registrationForm->isValid()) {
                if (!$registrationLimiter->create($request->getClientIp())->consume()->isAccepted()) {
                    $registrationForm->addError(new FormError('Trop de tentatives. Merci de réessayer plus tard.'));
                } else {
                    /** @var User $user */
                    $user = $registrationForm->getData();
                    $userRegistrar->registerWithPassword($user, $registrationForm->get('plainPassword')->getData());

                    $this->addFlash('success', 'Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter.');

                    return $this->redirectToRoute('app_login');
                }
            }
        }

        return $this->render('registration/register.html.twig', [
            'quickForm' => $quickForm,
            'registrationForm' => $registrationForm,
        ]);
    }
}
