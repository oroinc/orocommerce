<?php

namespace Oro\Bundle\CMSBundle\Layout\DataProvider;

use Oro\Bundle\CMSBundle\Provider\RequestPageProvider;
use Oro\Bundle\WebCatalogBundle\Layout\DataProvider\TitleDataProviderInterface;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentVariantProvider;

/**
 * Checks whether title could be displayed or not for cms page
 */
class TitleDataProvider implements TitleDataProviderInterface
{
    public function __construct(
        private readonly TitleDataProviderInterface $decoratedTitleDataProvider,
        private readonly RequestPageProvider $requestPageProvider,
        private readonly RequestWebContentVariantProvider $requestWebContentVariantProvider
    ) {
    }

    public function getNodeTitle($default = '')
    {
        return $this->getTitle($default);
    }

    public function getTitle($default = '', $data = null)
    {
        return $this->decoratedTitleDataProvider->getTitle($default);
    }

    public function isRenderTitle(): bool
    {
        $contentVariant = $this->requestWebContentVariantProvider->getContentVariant();
        $page = $this->requestPageProvider->getPage();

        if (!$contentVariant && $page) {
            return !$page->isDoNotRenderTitle();
        }

        return $this->decoratedTitleDataProvider->isRenderTitle();
    }
}
