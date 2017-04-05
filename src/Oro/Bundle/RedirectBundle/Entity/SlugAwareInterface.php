<?php

namespace Oro\Bundle\RedirectBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\Localization;

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
     * @param Localization $localization
     * @return Slug|null
     */
    public function getSlugByLocalization(Localization $localization = null);
}
