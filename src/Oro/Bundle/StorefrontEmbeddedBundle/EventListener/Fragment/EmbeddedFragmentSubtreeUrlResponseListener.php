<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\EventListener\Fragment;

use Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResponseEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * Adds a subtree URL to the embedded fragment response content.
 */
final class EmbeddedFragmentSubtreeUrlResponseListener
{
    public function onFragmentResponse(EmbeddedFragmentResponseEvent $event): void
    {
        $request = $event->getContextValue(EmbeddedFragmentResponseEvent::CONTEXT_REQUEST);
        if (!$request instanceof Request) {
            return;
        }

        $event->setContentValue('subtreeUrl', $request->getPathInfo());
    }
}
