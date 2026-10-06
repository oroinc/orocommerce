<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\EventListener\Fragment;

use Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResolveEvent;
use Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResponseEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * Adds a plain title to the embedded fragment response content.
 */
final class EmbeddedFragmentTitleListener
{
    public function onFragmentResolve(EmbeddedFragmentResolveEvent $event): void
    {
        if (!$this->isGetRequest($event->getContextValue(EmbeddedFragmentResolveEvent::CONTEXT_REQUEST))) {
            return;
        }

        $event->addBlockId('title');
    }

    public function onFragmentResponse(EmbeddedFragmentResponseEvent $event): void
    {
        if (!$this->isGetRequest($event->getContextValue(EmbeddedFragmentResponseEvent::CONTEXT_REQUEST))) {
            return;
        }

        $renderedTitleBlock = $event->getContentValue('title');

        $event->setContentValue(
            'title',
            \is_string($renderedTitleBlock)
                ? trim(html_entity_decode(strip_tags($renderedTitleBlock), ENT_QUOTES | ENT_HTML5, 'UTF-8'))
                : ''
        );
    }

    private function isGetRequest(mixed $request): bool
    {
        return $request instanceof Request && $request->isMethod(Request::METHOD_GET);
    }
}
