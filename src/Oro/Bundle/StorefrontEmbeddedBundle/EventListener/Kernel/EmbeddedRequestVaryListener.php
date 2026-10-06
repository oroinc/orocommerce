<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\EventListener\Kernel;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\NavigationBundle\Event\ResponseHashnavListener;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Adds the embedded request headers to the Vary header, so a shared cache keeps fragment and regular responses
 * apart.
 */
class EmbeddedRequestVaryListener
{
    public function __construct(
        private readonly FrontendHelper $frontendHelper,
        private readonly EmbeddedRequestChecker $embeddedRequestChecker,
    ) {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->frontendHelper->isFrontendRequest()) {
            return;
        }

        $vary = [EmbeddedRequestChecker::EMBEDDED_HEADER];
        if ($this->embeddedRequestChecker->isEmbeddedRequest()) {
            $vary[] = ResponseHashnavListener::HASH_NAVIGATION_HEADER;
        }

        $event->getResponse()->setVary($vary, false);
    }
}
