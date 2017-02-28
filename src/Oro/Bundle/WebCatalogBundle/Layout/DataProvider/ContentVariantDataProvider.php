<?php

namespace Oro\Bundle\WebCatalogBundle\Layout\DataProvider;

use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Symfony\Component\HttpFoundation\RequestStack;

class ContentVariantDataProvider
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @return ContentVariant|null
     */
    public function getFromRequest()
    {
        $contentVariant = null;
        $request = $this->requestStack->getCurrentRequest();

        if ($request && $request->attributes->has('_content_variant')) {
            $contentVariant = $request->attributes->get('_content_variant');
        }

        return $contentVariant;
    }
}
