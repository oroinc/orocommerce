<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Api\Stub;

use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptionsInterface;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;

class ProductShippingOptionsStub implements ProductShippingOptionsInterface
{
    private ?Weight $weight;

    private ?Dimensions $dimensions;

    #[\Override]
    public function getDimensions(): ?Dimensions
    {
        return $this->dimensions;
    }

    public function setDimensions(?Dimensions $dimensions): void
    {
        $this->dimensions = $dimensions;
    }

    #[\Override]
    public function getWeight(): ?Weight
    {
        return $this->weight;
    }

    public function setWeight(?Weight $weight): void
    {
        $this->weight = $weight;
    }

    #[\Override]
    public function getProduct(): void
    {
    }

    #[\Override]
    public function getProductUnit(): void
    {
    }
}
