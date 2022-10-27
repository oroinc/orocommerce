<?php

namespace Oro\Bundle\RedirectBundle\Model;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

class SlugPrototypesWithRedirect
{
    /**
     * @var Collection|LocalizedFallbackValue[]
     */
    private $slugPrototypes;

    /**
     * @var bool
     */
    private $createRedirect;

    /**
     * @param Collection $slugPrototypes
     * @param bool $createRedirect
     */
    public function __construct(Collection $slugPrototypes, $createRedirect = true)
    {
        $this->slugPrototypes = $slugPrototypes;
        $this->createRedirect = $createRedirect;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getSlugPrototypes()
    {
        return $this->slugPrototypes;
    }

    /**
     * @param LocalizedFallbackValue $slugPrototype
     * @return $this
     */
    public function addSlugPrototype(LocalizedFallbackValue $slugPrototype)
    {
        if (!$this->hasSlugPrototype($slugPrototype)) {
            $this->slugPrototypes->add($slugPrototype);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $slugPrototype
     * @return $this
     */
    public function removeSlugPrototype(LocalizedFallbackValue $slugPrototype)
    {
        if ($this->hasSlugPrototype($slugPrototype)) {
            $this->slugPrototypes->removeElement($slugPrototype);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $slugPrototype
     * @return bool
     */
    public function hasSlugPrototype(LocalizedFallbackValue $slugPrototype)
    {
        return $this->slugPrototypes->contains($slugPrototype);
    }

    /**
     * @return boolean
     */
    public function getCreateRedirect()
    {
        return $this->createRedirect;
    }

    /**
     * @param boolean $createRedirect
     * @return $this
     */
    public function setCreateRedirect($createRedirect)
    {
        $this->createRedirect = $createRedirect;

        return $this;
    }
}
