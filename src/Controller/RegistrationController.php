<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\QuickRegistrationFormType;
use App\Form\RegistrationFormType;
use App\Repository\CityRepository;
use App\Repository\UserRepository;
use App\Service\SlugGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        CityRepository $cityRepository,
        SlugGenerator $slugGenerator,
        MailerInterface $mailer,
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
            } else {
                $user = new User();
                $user->setEmail($email);
                $user->setCity($quickForm->get('city')->getData());
                $user->setUsername($this->generateUniqueUsername($email, $slugGenerator, $userRepository));

                $em->persist($user);
                $em->flush();

                $this->sendMagicLinkEmail($user, $mailer);

                $this->addFlash('success', 'Votre compte a été créé. Consultez vos emails pour recevoir votre lien de connexion.');

                return $this->redirectToRoute('app_login');
            }
        }

        $registrationForm = $this->createForm(RegistrationFormType::class, (new User())->setCity($prefillCity));

        if (!$quickForm->isSubmitted()) {
            $registrationForm->handleRequest($request);

            if ($registrationForm->isSubmitted() && $registrationForm->isValid()) {
                /** @var User $user */
                $user = $registrationForm->getData();
                $user->setPassword(
                    $passwordHasher->hashPassword($user, $registrationForm->get('plainPassword')->getData())
                );

                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter.');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('registration/register.html.twig', [
            'quickForm' => $quickForm,
            'registrationForm' => $registrationForm,
        ]);
    }

    private function generateUniqueUsername(string $email, SlugGenerator $slugGenerator, UserRepository $userRepository): string
    {
        $base = $slugGenerator->generate(strstr($email, '@', true) ?: $email);
        $username = $base;

        while ($userRepository->findOneBy(['username' => $username])) {
            $username = $base . '-' . random_int(100, 999);
        }

        return $username;
    }

    private function sendMagicLinkEmail(User $user, MailerInterface $mailer): void
    {
        $mailer->send(
            (new TemplatedEmail())
                ->from(new Address('noreply@challenge-velo.bzh', 'Challenge Vélo'))
                ->to($user->getEmail())
                ->subject('Votre lien de connexion')
                ->htmlTemplate('emails/magic_link.html.twig')
                ->context([
                    'user' => $user,
                    'loginUrl' => $this->generateUrl('app_login', ['token' => $user->getPersonalToken()], UrlGeneratorInterface::ABSOLUTE_URL),
                ])
        );
    }
}
