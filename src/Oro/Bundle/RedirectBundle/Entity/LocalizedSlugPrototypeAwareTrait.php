<?php

namespace Oro\Bundle\RedirectBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

/**
 * Trait for entities which implement LocalizedSlugPrototypeAwareInterface.
 * Contains validation constraints on slugPrototypes relation which is useful when validation is executed on entity
 * itself (e.g. during import).
 */
trait LocalizedSlugPrototypeAwareTrait
{
    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @Symfony\Component\Validator\Constraints\All(
     *     constraints = {
     *         @Oro\Bundle\RedirectBundle\Validator\Constraints\UrlSafeSlugPrototype()
     *     }
     * )
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     */
    protected $slugPrototypes;

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
}
