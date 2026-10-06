<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Request;

use Oro\Bundle\NavigationBundle\Event\ResponseHashnavListener;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tells if the storefront renders for an embedded host, which marks its requests with the X-Oro-Embedded header.
 */
class EmbeddedRequestChecker
{
    public const string EMBEDDED_HEADER = 'X-Oro-Embedded';

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function isEmbeddedRequest(): bool
    {
        $request = $this->requestStack->getMainRequest();

        return (bool)$request?->headers->has(self::EMBEDDED_HEADER);
    }

    /**
     * Tells if the fragment request reloads a layout block through hash navigation.
     */
    public function isEmbeddedRequestReload(): bool
    {
        $request = $this->requestStack->getMainRequest();

        return (bool)$request?->headers->get(ResponseHashnavListener::HASH_NAVIGATION_HEADER);
    }
}
