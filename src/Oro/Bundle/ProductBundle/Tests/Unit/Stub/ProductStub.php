<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Stub;

use Oro\Bundle\LocaleBundle\Entity\FallbackTrait;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;

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
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return LocalizedFallbackValue|null
     */
    public function getDefaultName(): ?LocalizedFallbackValue
    {
        return $this->getDefaultFallbackValue($this->names);
    }

    /**
     * @return LocalizedFallbackValue
     */
    public function getDefaultSlugPrototype(): ?LocalizedFallbackValue
    {
        return $this->getDefaultFallbackValue($this->slugPrototypes);
    }
}
