<?php

namespace Oro\Bundle\WebCatalogBundle\Generator\DTO;

use Oro\Bundle\LocaleBundle\Entity\Localization;

class SlugUrl
{
    /**
     * @var string
     */
    protected $url;
    
    /**
     * @var Localization
     */
    protected $localization;

    /**
     * @param string $url
     * @param Localization|null $localization
     */
    public function __construct($url, Localization $localization = null)
    {
        $this->url = $url;
        $this->localization = $localization;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return Localization
     */
    public function getLocalization()
    {
        return $this->localization;
    }
}
