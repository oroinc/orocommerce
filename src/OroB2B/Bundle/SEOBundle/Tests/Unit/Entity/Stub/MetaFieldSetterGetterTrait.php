<?php

namespace OroB2B\Bundle\SEOBundle\Tests\Unit\Entity\Stub;

trait MetaFieldSetterGetterTrait
{
    protected $metaTitles;
    protected $metaKeywords;
    protected $metaDescriptions;

    public function __construct()
    {
        $this->metaTitles = new \Doctrine\Common\Collections\ArrayCollection();
        $this->metaDescriptions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->metaKeywords = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function addMetaTitles($value)
    {
        if (!$this->metaTitles->contains($value)) {
            $this->metaTitles->add($value);
        }
    }

    public function addMetaKeywords($value)
    {
        if (!$this->metaKeywords->contains($value)) {
            $this->metaKeywords->add($value);
        }
    }

    public function addMetaDescriptions($value)
    {
        if (!$this->metaDescriptions->contains($value)) {
            $this->metaDescriptions->add($value);
        }
    }

    public function getMetaTitles()
    {
        return $this->metaTitles;
    }

    public function getMetaKeywords()
    {
        return $this->metaKeywords;
    }

    public function getMetaDescriptions()
    {
        return $this->metaDescriptions;
    }
}
