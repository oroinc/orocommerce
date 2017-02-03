<?php

namespace Oro\Bundle\RedirectBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;

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

    /**
     * @return SlugPrototypesWithRedirect
     */
    public function getSlugPrototypesWithRedirect();

    /**
     * @param SlugPrototypesWithRedirect $slugPrototypesWithRedirect
     *
     * @return $this
     */
    public function setSlugPrototypesWithRedirect(SlugPrototypesWithRedirect $slugPrototypesWithRedirect);
}
