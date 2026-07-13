<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class TokenLoginAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly LoginSuccessHandler $loginSuccessHandler,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return 'app_login_token_confirm' === $request->attributes->get('_route');
    }

    public function authenticate(Request $request): Passport
    {
        $csrfToken = new CsrfToken('login_token_confirm', $request->request->get('_csrf_token'));
        if (!$this->csrfTokenManager->isTokenValid($csrfToken)) {
            throw new InvalidCsrfTokenException('Jeton CSRF invalide.');
        }

        $token = $request->query->get('token');
        $user = \is_string($token) ? $this->userRepository->findOneByPersonalToken($token) : null;

        if (!$user) {
            throw new CustomUserMessageAuthenticationException('Lien de connexion invalide.');
        }

        return new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), fn () => $user));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return $this->loginSuccessHandler->onAuthenticationSuccess($request, $token);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->getFlashBag()->add('danger', 'Lien de connexion invalide.');

        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
