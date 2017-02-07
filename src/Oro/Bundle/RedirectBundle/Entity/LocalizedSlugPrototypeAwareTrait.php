<?php

namespace Oro\Bundle\RedirectBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;

trait LocalizedSlugPrototypeAwareTrait
{
    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     */
    protected $slugPrototypes;

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
