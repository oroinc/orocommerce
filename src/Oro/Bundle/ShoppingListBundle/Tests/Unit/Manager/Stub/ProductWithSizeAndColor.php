<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager\Stub;

use Oro\Bundle\ProductBundle\Entity\Product;

class ProductWithSizeAndColor extends Product
{
    /**
     * @var string
     */
    private $size;

    /**
     * @var string
     */
    private $color;

    /**
     * @return string
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param string $size
     * @return ProductWithSizeAndColor
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param string $color
     * @return ProductWithSizeAndColor
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }
}
