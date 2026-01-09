<?php

namespace Oro\Bundle\SEOBundle\Model\DTO;

use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Data transfer object representing an alternate URL for a sitemap entry.
 *
 * This class represents an alternate URL variant for a sitemap entry, typically used for hreflang links
 * to indicate alternate language or regional versions of a page. It associates a URL with an optional localization
 * to specify the language or region it represents, with support for the special 'x-default' language code.
 */
class AlternateUrl
{
    public const HREF_LANG_X_DEFAULT = 'x-default';

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
    public function __construct($url, ?Localization $localization = null)
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
