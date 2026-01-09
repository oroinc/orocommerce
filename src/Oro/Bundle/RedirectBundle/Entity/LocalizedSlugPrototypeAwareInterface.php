<?php

namespace Oro\Bundle\RedirectBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

/**
 * Defines the contract for entities that support localized slug prototypes.
 *
 * Entities implementing this interface can manage a collection of localized slug prototypes,
 * which are used as templates for generating URL slugs in different languages and locales.
 * This allows for multi-language URL generation and management.
 */
interface LocalizedSlugPrototypeAwareInterface
{
    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getSlugPrototypes();

    /**
     * @param LocalizedFallbackValue $slugPrototype
     *
     * @return $this
     */
    public function addSlugPrototype(LocalizedFallbackValue $slugPrototype);

    /**
     * @param LocalizedFallbackValue $slugPrototype
     *
     * @return $this
     */
    public function removeSlugPrototype(LocalizedFallbackValue $slugPrototype);

    /**
     * @param LocalizedFallbackValue $slugPrototype
     * @return bool
     */
    public function hasSlugPrototype(LocalizedFallbackValue $slugPrototype);
}
