<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return new RedirectResponse($this->urlGenerator->generate('app_home'));
        }

        if ($user->isSuperAdmin()) {
            $url = $this->urlGenerator->generate('app_super_admin_dashboard');
        } elseif ($user->isAdminCity() && $user->getCity() !== null) {
            $url = $this->urlGenerator->generate('app_city_admin_dashboard', [
                'citySlug' => $user->getCity()->getSlug(),
            ]);
        } elseif ($user->getCity() !== null) {
            $url = $this->urlGenerator->generate('app_my_challenge', [
                'citySlug' => $user->getCity()->getSlug(),
            ]);
        } else {
            $url = $this->urlGenerator->generate('app_home');
        }

        return new RedirectResponse($url);
    }
}
