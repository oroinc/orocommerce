<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\EventListener\Fragment;

use Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResolveEvent;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Symfony\Component\HttpFoundation\Request;

/**
 * Adds the layout_js_modules_config block to the blocks a fragment request renders.
 */
final class EmbeddedFragmentJsModulesConfigBlockListener
{
    public function __construct(
        private readonly EmbeddedRequestChecker $embeddedRequestChecker,
    ) {
    }

    public function onFragmentResolve(EmbeddedFragmentResolveEvent $event): void
    {
        $request = $event->getContextValue(EmbeddedFragmentResolveEvent::CONTEXT_REQUEST);
        if (!$request instanceof Request || $this->embeddedRequestChecker->isEmbeddedRequestReload($request)) {
            return;
        }

        $event->addBlockId('layout_js_modules_config');
    }
}
