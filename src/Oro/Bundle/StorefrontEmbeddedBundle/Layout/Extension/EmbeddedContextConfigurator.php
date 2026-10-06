<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Layout\Extension;

use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Adds the `is_embedded_fragment` flag to the layout context.
 */
class EmbeddedContextConfigurator implements ContextConfiguratorInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EmbeddedRequestChecker $embeddedRequestChecker,
    ) {
    }

    #[\Override]
    public function configureContext(ContextInterface $context): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $context->getResolver()
            ->define('is_embedded_fragment')
            ->allowedTypes('bool')
            ->default($this->embeddedRequestChecker->isEmbeddedFragmentRequest($request));
    }
}
