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
    #[\Override]
    public function __set($name, $value)
    {
        $this->values[$name] = $value;
    }

    /**
     * @param string $name
     * @return mixed
     */
    #[\Override]
    public function __get($name)
    {
        return isset($this->values[$name]) ? $this->values[$name] : null;
    }

    #[\Override]
    public function set(string $name, mixed $value): static
    {
        $this->__set($name, $value);

        return $this;
    }

    #[\Override]
    public function get(string $name): mixed
    {
        return $this->__get($name);
    }

    /**
     * @param string $name
     * @return bool
     */
    #[\Override]
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

    public function getName(?Localization $localization = null): ?ProductName
    {
        return $this->getFallbackValue($this->names, $localization);
    }

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
