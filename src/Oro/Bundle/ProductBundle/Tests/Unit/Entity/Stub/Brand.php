<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\LocaleBundle\Tests\Unit\Entity\Stub\LocalizedEntityTrait;
use Oro\Bundle\ProductBundle\Entity\Brand as BaseBrand;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccess;

class Brand extends BaseBrand
{
    use LocalizedEntityTrait;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @var array
     */
    private $localizedFields = [
        'name' => 'names',
        'description' => 'descriptions',
        'shortDescription' => 'shortDescriptions',
    ];

    /**
     * {@inheritdoc}
     */
    public function __call($name, $arguments)
    {
        return $this->localizedMethodCall($this->localizedFields, $name, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->localizedFields)) {
            return $this->localizedFieldGet($this->localizedFields, $name);
        } else {
            $this->getPropertyAccessor()->getValue($this, $name);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function __set($name, $value)
    {
        if (array_key_exists($name, $this->localizedFields)) {
            return $this->localizedFieldSet($this->localizedFields, $name, $value);
        } else {
            //PropertyAccessor::setValue() not work in this case
            $reflection = new \ReflectionProperty(self::class, $name);
            $reflection->setAccessible(true);
            $reflection->setValue($this, $value);
        }

        return null;
    }

    /**
     * @return PropertyAccessor
     */
    private function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
