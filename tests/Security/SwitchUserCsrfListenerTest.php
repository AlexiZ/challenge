<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\EventListener\SwitchUserCsrfListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

class SwitchUserCsrfListenerTest extends TestCase
{
    /** @return iterable<string, array{string, string, bool}> */
    public static function requests(): iterable
    {
        yield 'GET link (Turbo prefetch)' => ['GET', 'good', false];
        yield 'POST without valid token' => ['POST', 'bad', false];
        yield 'POST with valid token' => ['POST', 'good', true];
    }

    #[DataProvider('requests')]
    public function testSwitchRequiresPostWithCsrfToken(string $method, string $csrfToken, bool $allowed): void
    {
        $csrf = $this->createStub(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturnCallback(
            static fn (CsrfToken $t) => SwitchUserCsrfListener::TOKEN_ID === $t->getId() && 'good' === $t->getValue()
        );

        $request = Request::create('/plabennec/', $method, ['_switch_user' => 'someone@example.com', '_csrf_token' => $csrfToken]);

        if (!$allowed) {
            $this->expectException(AccessDeniedException::class);
        }

        (new SwitchUserCsrfListener($csrf))(new SwitchUserEvent($request, new User()));
        $this->addToAssertionCount(1);
    }
}
