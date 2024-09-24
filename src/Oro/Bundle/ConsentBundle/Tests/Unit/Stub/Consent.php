<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Stub;

use Oro\Bundle\ConsentBundle\Entity\Consent as BaseConsent;
use Oro\Bundle\LocaleBundle\Tests\Unit\Entity\Stub\LocalizedEntityTrait;

class Consent extends BaseConsent
{
    use LocalizedEntityTrait;

    /**
     * @var array
     */
    protected $localizedFields = [
        'name' => 'names',
    ];

    #[\Override]
    public function __call($name, $arguments)
    {
        return $this->localizedMethodCall($this->localizedFields, $name, $arguments);
    }

    #[\Override]
    public function __get($name)
    {
        return $this->localizedFieldGet($this->localizedFields, $name);
    }

    #[\Override]
    public function __set($name, $value)
    {
        return $this->localizedFieldSet($this->localizedFields, $name, $value);
    }
}
