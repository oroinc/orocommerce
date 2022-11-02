<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Entity\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

trait MetaFieldSetterGetterTrait
{
    /**
     * @var LocalizedFallbackValue[]
     */
    protected $metaTitles;

    /**
     * @var LocalizedFallbackValue[]
     */
    protected $metaKeywords;

    /**
     * @var LocalizedFallbackValue[]
     */
    protected $metaDescriptions;

    public function __construct()
    {
        $this->metaTitles = new ArrayCollection();
        $this->metaDescriptions = new ArrayCollection();
        $this->metaKeywords = new ArrayCollection();
    }

    /**
     * @param string $value
     */
    public function addMetaTitles($value)
    {
        if (!$this->metaTitles->contains($value)) {
            $this->metaTitles->add($value);
        }
    }

    /**
     * @param string $value
     */
    public function addMetaKeywords($value)
    {
        if (!$this->metaKeywords->contains($value)) {
            $this->metaKeywords->add($value);
        }
    }

    /**
     * @param string $value
     */
    public function addMetaDescriptions($value)
    {
        if (!$this->metaDescriptions->contains($value)) {
            $this->metaDescriptions->add($value);
        }
    }

    public function getMetaDescription(Localization $localization): LocalizedFallbackValue
    {
        return $this->metaDescriptions->current();
    }

    /**
     * @return LocalizedFallbackValue[]|Collection
     */
    public function getMetaTitles()
    {
        return $this->metaTitles;
    }

    /**
     * @return LocalizedFallbackValue[]|Collection
     */
    public function getMetaKeywords()
    {
        return $this->metaKeywords;
    }

    /**
     * @return LocalizedFallbackValue[]|Collection
     */
    public function getMetaDescriptions()
    {
        return $this->metaDescriptions;
    }

    public function setMetaTitles(Collection $values)
    {
        $this->metaTitles = $values;
    }

    public function setMetaKeywords(Collection $values)
    {
        $this->metaKeywords = $values;
    }

    public function setMetaDescriptions(Collection $values)
    {
        $this->metaDescriptions = $values;
    }
}
