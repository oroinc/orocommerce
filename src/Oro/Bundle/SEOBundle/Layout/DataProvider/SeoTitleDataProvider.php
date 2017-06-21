<?php

namespace Oro\Bundle\SEOBundle\Layout\DataProvider;

use Oro\Bundle\WebCatalogBundle\Layout\DataProvider\TitleDataProvider;

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


    public function __construct(
        SeoDataProvider $seoDataProvider,
        TitleDataProvider $titleDataProvider
    ) {
        $this->seoDataProvider = $seoDataProvider;
        $this->titleDataProvider = $titleDataProvider;
    }

    /**
     * @param $defaultValue
     * @param $data
     *
     * @return mixed
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
