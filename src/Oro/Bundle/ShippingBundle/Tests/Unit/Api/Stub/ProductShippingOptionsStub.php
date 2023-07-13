<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Api\Stub;

use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptionsInterface;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;

class ProductShippingOptionsStub implements ProductShippingOptionsInterface
{
    private ?Weight $weight;

    private ?Dimensions $dimensions;

    public function getDimensions(): ?Dimensions
    {
        return $this->dimensions;
    }

    public function setDimensions(?Dimensions $dimensions): void
    {
        $this->dimensions = $dimensions;
    }

    public function getWeight(): ?Weight
    {
        return $this->weight;
    }

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
