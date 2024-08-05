<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Oro\Bundle\CMSBundle\Entity\Page;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The provider of the web content variant for the current storefront request.
 */
class RequestPageProvider
{
    private const REQUEST_PAGE_ATTRIBUTE = 'page';

    public function __construct(
        private readonly RequestStack $requestStack
    ) {
    }

    public function getPage(): ?Page
    {
        $request = $this->requestStack->getCurrentRequest();

        return $request?->attributes->get(self::REQUEST_PAGE_ATTRIBUTE);
    }
}
