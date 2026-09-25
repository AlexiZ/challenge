<?php

namespace App\Service;

use App\Entity\City;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Creates accounts from the registration form. The password is optional: without
 * one, the user gets a magic login link by email instead. Kept as a service
 * (rather than controller-private methods) so username generation and the
 * magic-link email can be unit-tested in isolation.
 */
class UserRegistrar
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly SlugGenerator $slugGenerator,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ActiveCityEditionResolver $cityEditionResolver,
        private readonly string $mailerFromAddress,
    ) {}

    public function register(User $user, ?string $plainPassword): User
    {
        if ($user->getUsername() === '') {
            $user->setUsername($this->generateUniqueUsername($user->getEmail()));
        }

        if ($plainPassword !== null && $plainPassword !== '') {
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        }

        $this->addAsParticipant($user, $user->getCity());

        $this->em->persist($user);
        $this->em->flush();

        if ($user->getPassword() === null) {
            $this->sendMagicLinkEmail($user);
        }

        return $user;
    }

    private function addAsParticipant(User $user, ?City $city): void
    {
        if ($city === null) {
            return;
        }

        try {
            $this->cityEditionResolver->resolve($city->getSlug())->addParticipant($user);
        } catch (NotFoundHttpException) {
            // No edition exists yet for this city; nothing to attach to.
        }
    }

    private function generateUniqueUsername(string $email): string
    {
        $base = $this->slugGenerator->generate(strstr($email, '@', true) ?: $email);
        $username = $base;

        while ($this->userRepository->findOneBy(['username' => $username])) {
            $username = $base . '-' . random_int(100, 999);
        }

        return $username;
    }

    public function sendMagicLinkEmail(User $user): void
    {
        $this->mailer->send(
            (new TemplatedEmail())
                ->from(new Address($this->mailerFromAddress, 'Challenge Vélo'))
                ->to($user->getEmail())
                ->subject('Votre lien de connexion')
                ->htmlTemplate('emails/magic_link.html.twig')
                ->context([
                    'user' => $user,
                    'loginUrl' => $this->urlGenerator->generate('app_login', ['token' => $user->getPersonalToken()], UrlGeneratorInterface::ABSOLUTE_URL),
                ])
        );
    }
}
