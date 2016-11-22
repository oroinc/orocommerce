<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Extension\Stub;

use Oro\Bundle\CatalogBundle\Entity\Category;

class CategoryStub extends Category
{
    /** @var  mixed smallImage */
    protected $smallImage;

    /** @var  mixed largeImage */
    protected $largeImage;

    /**
     * @return mixed
     */
    public function getSmallImage()
    {
        return $this->smallImage;
    }

    /**
     * @param mixed $smallImage
     * @return CategoryStub
     */
    public function setSmallImage($smallImage)
    {
        $this->smallImage = $smallImage;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLargeImage()
    {
        return $this->largeImage;
    }

    /**
     * @param mixed $largeImage
     * @return CategoryStub
     */
    public function setLargeImage($largeImage)
    {
        $this->largeImage = $largeImage;
        return $this;
    }
}
