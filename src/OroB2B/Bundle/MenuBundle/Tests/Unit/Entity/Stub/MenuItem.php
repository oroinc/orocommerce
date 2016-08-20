<?php

namespace Oro\Bundle\MenuBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\LocaleBundle\Tests\Unit\Entity\Stub\LocalizedEntityTrait;
use Oro\Bundle\MenuBundle\Entity\MenuItem as BaseMenuItem;

class MenuItem extends BaseMenuItem
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

    /**
     * @var File
     */
    protected $image;

    /**
     * @return File[]
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param File[] $image
     * @return $this
     */
    public function setImage(array $image)
    {
        $this->image = $image;

        return $this;
    }
}
