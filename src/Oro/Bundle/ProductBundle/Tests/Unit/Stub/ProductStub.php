<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Stub;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\LocaleBundle\Entity\FallbackTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;

class ProductStub extends Product
{
    use FallbackTrait;

    /** @var array */
    private $values = [];

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->values[$name] = $value;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return isset($this->values[$name]) ? $this->values[$name] : null;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->values[$name]);
    }

    /**
     * @param int $id
     * @return ProductStub
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getDefaultName(): ?ProductName
    {
        return $this->getFallbackValue($this->names);
    }

    public function getName(Localization $localization = null): ?ProductName
    {
        return $this->getFallbackValue($this->names, $localization);
    }

    /**
     * @return LocalizedFallbackValue
     */
    public function getDefaultSlugPrototype(): ?LocalizedFallbackValue
    {
        return $this->getDefaultFallbackValue($this->slugPrototypes);
    }

    public function setUnitPrecisions(Collection $collection): self
    {
        $this->unitPrecisions = $collection;

        return $this;
    }
}
