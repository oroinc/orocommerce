<?php

namespace Oro\Bundle\RedirectBundle\Generator\DTO;

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
     * @var string|null
     */
    protected $slug;

    /**
     * @param string $url
     * @param Localization|null $localization
     * @param string|null $slug
     */
    public function __construct($url, Localization $localization = null, $slug = null)
    {
        $this->url = $url;
        $this->localization = $localization;
        $this->slug = $slug;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return Localization
     */
    public function getLocalization()
    {
        return $this->localization;
    }

    /**
     * @return null|string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     * @return $this
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }
}
