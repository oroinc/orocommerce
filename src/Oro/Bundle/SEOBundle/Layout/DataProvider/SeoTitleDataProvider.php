<?php

namespace Oro\Bundle\SEOBundle\Layout\DataProvider;

use Oro\Bundle\WebCatalogBundle\Layout\DataProvider\TitleDataProvider;

/**
 * Decorates `web_catalog_title` layout data provider.
 * Returns web catalog meta title as a page title.
 */
class SeoTitleDataProvider
{
    /**
     * @var TitleDataProvider
     */
    protected $titleDataProvider;

    /**
     * @var SeoDataProvider
     */
    private $seoDataProvider;

    /**
     * @param SeoDataProvider $seoDataProvider
     * @param TitleDataProvider $titleDataProvider
     */
    public function __construct(
        SeoDataProvider $seoDataProvider,
        TitleDataProvider $titleDataProvider
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
    public function getTitle($defaultValue, $data = null)
    {
        $value = $data ?
            $this->seoDataProvider->getMetaInformation($data, 'metaTitles') :
            $this->seoDataProvider->getMetaInformationFromContentNode('metaTitles');

        if (!$value || !$value->getString()) {
            $value = $this->titleDataProvider->getTitle($defaultValue);
        }

        return $value;
    }
}
