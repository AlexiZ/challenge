<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;

/**
 * Wraps every HTML email in the Brevo template (header + footer): the rendered
 * HTML goes into the template's {{params.BODY}}. Runs after the Twig
 * MessageListener (priority 0) so TemplatedEmail bodies are already rendered.
 * Non-Brevo transports ignore these headers and send the raw HTML.
 */
#[AsEventListener(event: MessageEvent::class, priority: -10)]
class BrevoTemplateListener
{
    private const TEMPLATE_ID = 6;

    public function __invoke(MessageEvent $event): void
    {
        $email = $event->getMessage();
        if (!$email instanceof Email || null === $email->getHtmlBody()) {
            return;
        }

        $headers = $email->getHeaders();
        if ($headers->has('templateId')) {
            return;
        }

        $headers->addTextHeader('templateId', (string) self::TEMPLATE_ID);
        $headers->addParameterizedHeader('params', 'params', ['BODY' => $email->getHtmlBody()]);
    }
}
