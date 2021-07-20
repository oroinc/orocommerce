<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Entity\SluggableTrait;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;

class SluggableEntityStub implements DatesAwareInterface, SluggableInterface
{
    use DatesAwareTrait;
    use SluggableTrait;

    /**
     * @var int
     */
    private $id;

    /**
     * @var LocalizedFallbackValue
     */
    private $defaultSlugPrototype;

    /**
     * @var Collection|LocalizedFallbackValue[]
     */
    protected $titles;

    public function __construct()
    {
        $this->slugPrototypes = new ArrayCollection();
        $this->slugs = new ArrayCollection();
        $this->titles = new ArrayCollection();
        $this->slugPrototypesWithRedirect = new SlugPrototypesWithRedirect($this->slugPrototypes);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return SluggableEntityStub
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return LocalizedFallbackValue
     */
    public function getDefaultSlugPrototype()
    {
        return $this->defaultSlugPrototype;
    }

    /**
     * @param LocalizedFallbackValue $slugPrototype
     * @return $this
     */
    public function setDefaultSlugPrototype(LocalizedFallbackValue $slugPrototype)
    {
        $this->defaultSlugPrototype = $slugPrototype;

        return $this;
    }

    /**
     * @return Collection|LocalizedFallbackValue[]
     */
    public function getTitles(): Collection
    {
        return $this->titles;
    }

    public function addTitle(LocalizedFallbackValue $title): self
    {
        if (!$this->titles->contains($title)) {
            $this->titles->add($title);
        }

        return $this;
    }

    public function removeTitle(LocalizedFallbackValue $title): self
    {
        if ($this->titles->contains($title)) {
            $this->titles->removeElement($title);
        }

        return $this;
    }
}
