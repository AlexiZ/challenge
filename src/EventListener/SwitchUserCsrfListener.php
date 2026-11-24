<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * Impersonation (switch and exit) only through a POST form with a CSRF token.
 * Symfony's switch_user also accepts GET ?_switch_user=…, which Turbo prefetches
 * on hover: hovering a link was enough to switch the session. The event is
 * dispatched before the token changes, so throwing here aborts the switch (403).
 */
#[AsEventListener(event: SecurityEvents::SWITCH_USER)]
class SwitchUserCsrfListener
{
    public const TOKEN_ID = 'switch_user';

    public function __construct(private readonly CsrfTokenManagerInterface $csrfTokenManager)
    {
    }

    public function __invoke(SwitchUserEvent $event): void
    {
        $request = $event->getRequest();
        $token = new CsrfToken(self::TOKEN_ID, $request->request->getString('_csrf_token'));

        if (!$request->isMethod('POST') || !$this->csrfTokenManager->isTokenValid($token)) {
            throw new AccessDeniedException('Jeton CSRF invalide.');
        }
    }
}
