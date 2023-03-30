<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\LocaleBundle\Tests\Unit\Entity\Stub\LocalizedEntityTrait;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem as BaseProductKitItem;

class ProductKitItemStub extends BaseProductKitItem
{
    use LocalizedEntityTrait;

    private array $localizedFields = [
        'label' => 'labels',
    ];

    public function __construct(?int $id = null)
    {
        parent::__construct();

        $this->id = $id;
    }

    public function __call($name, $arguments)
    {
        return $this->localizedMethodCall($this->localizedFields, $name, $arguments);
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->localizedFields)) {
            return $this->localizedFieldGet($this->localizedFields, $name);
        }

        if (property_exists($this, $name)) {
            return $this->$name;
        }

        throw new \RuntimeException('It\'s not expected to get non-existing property');
    }

    public function __set($name, $value)
    {
        if (array_key_exists($name, $this->localizedFields)) {
            $this->localizedFieldSet($this->localizedFields, $name, $value);

            return;
        }

        if (property_exists($this, $name)) {
            $this->$name = $value;

            return;
        }

        throw new \RuntimeException('It\'s not expected to set non-existing property');
    }

    public function __isset($name)
    {
        if (array_key_exists($name, $this->localizedFields)) {
            return (bool)$this->localizedFieldGet($this->localizedFields, $name);
        }

        if (property_exists($this, $name)) {
            return true;
        }

        return false;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }
}
