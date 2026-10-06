<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Request;

use Oro\Bundle\NavigationBundle\Event\ResponseHashnavListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A toolkit for checking embedded requests.
 */
class EmbeddedRequestChecker
{
    public const string EMBEDDED_HEADER = 'X-Oro-Embedded';
    public const string EMBEDDED_FRAGMENT_NAME = '_embedded_fragment_name';

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    /**
     * @param Request|null $request The main request when null.
     */
    public function isEmbeddedRequest(?Request $request = null): bool
    {
        $request ??= $this->requestStack->getMainRequest();

        return (bool)$request?->headers->has(self::EMBEDDED_HEADER);
    }

    /**
     * @param Request|null $request The main request when null.
     */
    public function isEmbeddedFragmentRequest(?Request $request = null): bool
    {
        return $this->getEmbeddedFragmentName($request) !== null;
    }

    /**
     * @param Request|null $request The main request when null.
     */
    public function getEmbeddedFragmentName(?Request $request = null): ?string
    {
        $request ??= $this->requestStack->getMainRequest();
        $fragmentName = $request?->attributes->get(self::EMBEDDED_FRAGMENT_NAME);

        return \is_string($fragmentName) ? $fragmentName : null;
    }

    /**
     * @param Request|null $request The main request when null.
     */
    public function isEmbeddedRequestReload(?Request $request = null): bool
    {
        $request ??= $this->requestStack->getMainRequest();

        return (bool)$request?->headers->get(ResponseHashnavListener::HASH_NAVIGATION_HEADER);
    }
}
