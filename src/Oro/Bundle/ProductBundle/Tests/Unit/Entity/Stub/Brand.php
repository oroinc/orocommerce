<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\LocaleBundle\Tests\Unit\Entity\Stub\LocalizedEntityTrait;
use Oro\Bundle\ProductBundle\Entity\Brand as BaseBrand;

class Brand extends BaseBrand
{
    use LocalizedEntityTrait;

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
    public function __call(string $name, array $arguments)
    {
        return $this->localizedMethodCall($this->localizedFields, $name, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function __get(string $name)
    {
        if (array_key_exists($name, $this->localizedFields)) {
            return $this->localizedFieldGet($this->localizedFields, $name);
        }

        if (property_exists($this, $name)) {
            return $this->$name;
        }

        throw new \RuntimeException('It\'s not expected to get non-existing property');
    }

    /**
     * {@inheritdoc}
     */
    public function __set(string $name, $value): void
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

    /**
     * {@inheritdoc}
     */
    public function __isset(string $name): bool
    {
        if (array_key_exists($name, $this->localizedFields)) {
            return (bool)$this->localizedFieldGet($this->localizedFields, $name);
        }

        if (property_exists($this, $name)) {
            return true;
        }

        return false;
    }

    public function cloneLocalizedFallbackValueAssociations(): self
    {
        return $this;
    }
}
