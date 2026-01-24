<?php

namespace Oro\Bundle\RedirectBundle\Generator\DTO;

use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Data Transfer Object encapsulating URL, localization, and slug prototype during slug generation.
 *
 * This DTO is used throughout the slug generation pipeline to carry the complete context needed
 * for creating or updating {@see Slug} entities. It combines the final URL (with prefixes applied),
 * the associated {@see Localization} for multi-language support, and the original slug prototype
 * (the base slug text before prefixes). This separation allows the slug generation system to track
 * both the source prototype and the final URL independently, which is essential for redirect management
 * and slug uniqueness resolution.
 */
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
    public function __construct($url, ?Localization $localization = null, $slug = null)
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
