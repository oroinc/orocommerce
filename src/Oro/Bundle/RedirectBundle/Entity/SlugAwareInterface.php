<?php

namespace Oro\Bundle\RedirectBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Defines the contract for entities that support URL slugs.
 *
 * Entities implementing this interface can manage a collection of URL slugs, which are
 * SEO-friendly URL identifiers for the entity. This interface provides methods to add, remove,
 * and retrieve slugs, including locale-specific slug retrieval for multi-language support.
 */
interface SlugAwareInterface
{
    /**
     * @return Collection|Slug[]
     */
    public function getSlugs();

    /**
     * @param Slug $slug
     * @return $this
     */
    public function addSlug(Slug $slug);

    /**
     * @param Slug $slug
     * @return $this
     */
    public function removeSlug(Slug $slug);

    /**
     * @return $this
     */
    public function resetSlugs();

    /**
     * @param Slug $slug
     * @return bool
     */
    public function hasSlug(Slug $slug);

    /**
     * @return Slug|null
     */
    public function getBaseSlug();

    /**
     * @param Localization|null $localization
     * @return Slug|null
     */
    public function getSlugByLocalization(?Localization $localization = null);
}
