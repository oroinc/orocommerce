<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\LocaleBundle\Tests\Unit\Entity\Stub\LocalizedEntityTrait;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode as BaseContentNode;

class ContentNode extends BaseContentNode
{
    use LocalizedEntityTrait;

    /**
     * @var array
     */
    protected $localizedFields = [
        'title' => 'titles',
    ];

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

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
