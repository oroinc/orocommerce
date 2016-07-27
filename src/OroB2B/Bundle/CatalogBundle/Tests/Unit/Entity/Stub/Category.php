<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\LocaleBundle\Tests\Unit\Entity\Stub\LocalizedEntityTrait;

use OroB2B\Bundle\CatalogBundle\Entity\Category as BaseCategory;

class Category extends BaseCategory
{
    use LocalizedEntityTrait;

    /**
     * {@inheritdoc}
     */
    public function __call($name, $arguments)
    {
        return $this->localizedMethodCall(['title' => 'titles'], $name, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        return $this->localizedFieldGet(['title' => 'titles'], $name);
    }

    /**
     * {@inheritdoc}
     */
    public function __set($name, $value)
    {
        return $this->localizedFieldSet(['title' => 'titles'], $name, $value);
    }
}
