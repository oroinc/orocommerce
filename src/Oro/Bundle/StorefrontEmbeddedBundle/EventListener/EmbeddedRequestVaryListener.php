<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\EventListener;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Tells caches that a storefront response depends on the headers an embedded host sends, so a shared cache in
 * front of Oro cannot hand a page rendered for a host — absolute asset urls, urls behind the host's proxy prefix,
 * paths left alone for the roots the host serves — to a visitor browsing Oro directly, or the other way round.
 */
class EmbeddedRequestVaryListener
{
    public function __construct(private readonly FrontendHelper $frontendHelper)
    {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->frontendHelper->isFrontendRequest()) {
            return;
        }

        $event->getResponse()->setVary(
            [
                EmbeddedRequestChecker::EMBEDDED_HEADER,
                EmbeddedRequestChecker::PROXY_PREFIX_HEADER,
                EmbeddedRequestChecker::ASSET_ROOTS_HEADER,
            ],
            false
        );
    }
}
