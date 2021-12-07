<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Api\Stub;

use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptionsInterface;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;

class ProductShippingOptionsStub implements ProductShippingOptionsInterface
{
    private ?Weight $weight;

    private ?Dimensions $dimensions;

    /**
     * @return Dimensions|null
     */
    public function getDimensions(): ?Dimensions
    {
        return $this->dimensions;
    }

    /**
     * @param Dimensions|null $dimensions
     */
    public function setDimensions(?Dimensions $dimensions): void
    {
        $this->dimensions = $dimensions;
    }

    /**
     * @return Weight|null
     */
    public function getWeight(): ?Weight
    {
        return $this->weight;
    }

    /**
     * @param Weight|null $weight
     */
    public function setWeight(?Weight $weight): void
    {
        $this->weight = $weight;
    }

    public function getProduct(): void
    {
    }

    public function getProductUnit(): void
    {
    }
}
