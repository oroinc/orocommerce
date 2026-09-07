<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\Twig;

use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Lets a layout tell whether the storefront is rendering for an embedded host and through which prefix that host
 * serves Oro, which is what the embedded page-config block keys on.
 */
class EmbeddedRequestExtension extends AbstractExtension
{
    public function __construct(private readonly EmbeddedRequestChecker $embeddedRequestChecker)
    {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'oro_storefront_embedded_request',
                fn (): bool => $this->embeddedRequestChecker->isEmbeddedRequest()
            ),
            new TwigFunction(
                'oro_storefront_embedded_proxy_prefix',
                fn (): string => $this->embeddedRequestChecker->proxyPrefix()
            ),
        ];
    }
}
