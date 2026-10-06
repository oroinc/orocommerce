<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\EventListener\Fragment;

use Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResponseEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

/**
 * Adds the session flash messages to the embedded fragment response of a GET request.
 */
final class EmbeddedFragmentFlashMessagesResponseListener
{
    public function onFragmentResponse(EmbeddedFragmentResponseEvent $event): void
    {
        $request = $event->getContextValue(EmbeddedFragmentResponseEvent::CONTEXT_REQUEST);
        if (!$request instanceof Request || !$request->isMethod(Request::METHOD_GET)) {
            return;
        }

        $session = $request->hasSession() ? $request->getSession() : null;

        $event->setContentValue(
            'flashMessages',
            $session instanceof FlashBagAwareSessionInterface ? $session->getFlashBag()->all() : []
        );
    }
}
