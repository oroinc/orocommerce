<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\EventListener\Fragment;

use Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResponseEvent;
use Oro\Bundle\StorefrontEmbeddedBundle\Provider\EmbeddedFragmentStylesMetadataProvider;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Symfony\Component\HttpFoundation\Request;

/**
 * Adds the styles metadata to the embedded fragment response of a GET request.
 */
final class EmbeddedFragmentStylesMetadataResponseListener
{
    public function __construct(
        private readonly EmbeddedFragmentStylesMetadataProvider $stylesMetadataProvider,
        private readonly EmbeddedRequestChecker $embeddedRequestChecker,
    ) {
    }

    public function onFragmentResponse(EmbeddedFragmentResponseEvent $event): void
    {
        $request = $event->getContextValue(EmbeddedFragmentResponseEvent::CONTEXT_REQUEST);
        if (
            !$request instanceof Request
            || !$request->isMethod(Request::METHOD_GET)
            || $this->embeddedRequestChecker->isEmbeddedRequestReload($request)
        ) {
            return;
        }

        foreach ($this->stylesMetadataProvider->getStylesMetadata() as $name => $value) {
            $event->setContentValue($name, $value);
        }
    }
}
