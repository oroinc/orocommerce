<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\CMSBundle\Entity\ContentBlock as BaseContentBlock;
use Oro\Bundle\LocaleBundle\Tests\Unit\Entity\Stub\LocalizedEntityTrait;

class ContentBlock extends BaseContentBlock
{
    use LocalizedEntityTrait;

    /**
     * @var array
     */
    protected $localizedFields = [
        'title' => 'titles',
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
