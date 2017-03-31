<?php

namespace Oro\Bundle\SEOBundle\Model\DTO;

use Oro\Bundle\LocaleBundle\Entity\Localization;

class AlternateUrl
{
    const HREF_LANG_X_DEFAULT = 'x-default';

    /**
     * @var string
     */
    private $url;

    /**
     * @var Localization|null
     */
    private $localization;

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
     * @return string
     */
    public function getLanguageCode()
    {
        if (!$this->localization) {
            return self::HREF_LANG_X_DEFAULT;
        }

        $languageCode = $this->localization->getLanguageCode();

        return str_replace('_', '-', strtolower($languageCode));
    }
}
