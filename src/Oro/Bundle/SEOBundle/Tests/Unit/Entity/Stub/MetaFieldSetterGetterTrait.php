<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Entity\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

trait MetaFieldSetterGetterTrait
{
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
        $this->metaDescriptions = new ArrayCollection();
        $this->metaKeywords = new ArrayCollection();
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
}
