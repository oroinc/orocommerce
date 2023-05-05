<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\ConsentBundle\Entity\Consent as BaseConsent;
use Oro\Bundle\LocaleBundle\Tests\Unit\Entity\Stub\LocalizedEntityTrait;

/**
 * Stub entity that adds extended functionality for the tests
 */
class Consent extends BaseConsent
{
    use LocalizedEntityTrait;

    /**
     * @var array
     */
    private $localizedFields = [
        'name' => 'names',
    ];

    /**
     * @var string
     */
    private $defaultName;

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
}
