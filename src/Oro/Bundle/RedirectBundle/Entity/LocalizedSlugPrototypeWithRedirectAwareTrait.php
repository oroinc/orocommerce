<?php

namespace Oro\Bundle\RedirectBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;

trait LocalizedSlugPrototypeWithRedirectAwareTrait
{
    use LocalizedSlugPrototypeAwareTrait;

    /**
     * @var SlugPrototypesWithRedirect
     */
    protected $slugPrototypesWithRedirect;

    /**
     * @return SlugPrototypesWithRedirect
     */
    public function getSlugPrototypesWithRedirect()
    {
        if (!$this->slugPrototypesWithRedirect) {
            $this->slugPrototypesWithRedirect = new SlugPrototypesWithRedirect($this->slugPrototypes);
        }

        return $this->slugPrototypesWithRedirect;
    }

    /**
     * @param SlugPrototypesWithRedirect $slugPrototypesWithRedirect
     * @return $this
     */
    public function setSlugPrototypesWithRedirect(SlugPrototypesWithRedirect $slugPrototypesWithRedirect)
    {
        $this->slugPrototypesWithRedirect = $slugPrototypesWithRedirect;

        return $this;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getSlugPrototypes()
    {
        return $this->getSlugPrototypesWithRedirect()->getSlugPrototypes();
    }

    /**
     * @param LocalizedFallbackValue $slugPrototype
     * @return $this
     */
    public function addSlugPrototype(LocalizedFallbackValue $slugPrototype)
    {
        if (!$this->getSlugPrototypesWithRedirect()->hasSlugPrototype($slugPrototype)) {
            $this->getSlugPrototypesWithRedirect()->getSlugPrototypes()->add($slugPrototype);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $slugPrototype
     * @return $this
     */
    public function removeSlugPrototype(LocalizedFallbackValue $slugPrototype)
    {
        if ($this->getSlugPrototypesWithRedirect()->hasSlugPrototype($slugPrototype)) {
            $this->getSlugPrototypesWithRedirect()->getSlugPrototypes()->removeElement($slugPrototype);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $slugPrototype
     * @return bool
     */
    public function hasSlugPrototype(LocalizedFallbackValue $slugPrototype)
    {
        return $this->getSlugPrototypesWithRedirect()->getSlugPrototypes()->contains($slugPrototype);
    }
}
