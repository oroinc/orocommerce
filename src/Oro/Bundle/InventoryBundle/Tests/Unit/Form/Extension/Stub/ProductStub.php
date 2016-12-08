<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension\Stub;

use Oro\Bundle\ProductBundle\Entity\Product;

class ProductStub extends Product
{
    /**
     * @var
     */
    protected $minimumQuantityToOrder;

    /**
     * @var
     */
    protected $maximumQuantityToOrder;

    /**
     * @return mixed
     */
    public function getMinimumQuantityToOrder()
    {
        return $this->minimumQuantityToOrder;
    }

    /**
     * @param mixed $minimumQuantityToOrder
     * @return $this
     */
    public function setMinimumQuantityToOrder($minimumQuantityToOrder)
    {
        $this->minimumQuantityToOrder = $minimumQuantityToOrder;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMaximumQuantityToOrder()
    {
        return $this->maximumQuantityToOrder;
    }

    /**
     * @param mixed $maximumQuantityToOrder
     * @return $this
     */
    public function setMaximumQuantityToOrder($maximumQuantityToOrder)
    {
        $this->maximumQuantityToOrder = $maximumQuantityToOrder;

        return $this;
    }
}
