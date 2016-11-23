<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\LocaleBundle\Tests\Unit\Entity\Stub\LocalizedEntityTrait;
use Oro\Bundle\CatalogBundle\Entity\Category as BaseCategory;
use Oro\Component\PropertyAccess\PropertyAccessor;

class Category extends BaseCategory
{
    use LocalizedEntityTrait;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @var array
     */
    protected $localizedFields = [
        'title' => 'titles',
        'shortDescription' => 'shortDescriptions',
        'longDescription' => 'longDescriptions',
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
