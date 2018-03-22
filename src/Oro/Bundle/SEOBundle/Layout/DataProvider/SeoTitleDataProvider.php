<?php

namespace Oro\Bundle\SEOBundle\Layout\DataProvider;

use Oro\Bundle\WebCatalogBundle\Layout\DataProvider\TitleDataProviderInterface;

/**
 * Decorates `web_catalog_title` layout data provider.
 * Returns web catalog meta title as a page title.
 */
class SeoTitleDataProvider implements TitleDataProviderInterface
{
    /**
     * @var TitleDataProviderInterface
     */
    private $titleDataProvider;

    /**
     * @var SeoDataProvider
     */
    private $seoDataProvider;

    /**
     * @param SeoDataProvider $seoDataProvider
     * @param TitleDataProviderInterface $titleDataProvider
     */
    public function __construct(
        SeoDataProvider $seoDataProvider,
        TitleDataProviderInterface $titleDataProvider
    ) {
        $this->seoDataProvider = $seoDataProvider;
        $this->titleDataProvider = $titleDataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeTitle($default = '')
    {
        return $this->titleDataProvider->getNodeTitle($default);
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle($default = '', $data = null)
    {
        $value = $data ?
            $this->seoDataProvider->getMetaInformation($data, 'metaTitles') :
            $this->seoDataProvider->getMetaInformationFromContentNode('metaTitles');

        if (!$value || !$value->getString()) {
            $value = $this->titleDataProvider->getTitle($default);
        }

        return $value;
    }
}
