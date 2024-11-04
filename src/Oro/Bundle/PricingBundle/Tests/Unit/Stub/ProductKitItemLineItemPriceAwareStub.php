<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\Stub;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemPriceAwareInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemStub;

class ProductKitItemLineItemPriceAwareStub extends ProductKitItemLineItemStub implements
    ProductKitItemLineItemPriceAwareInterface
{
    private ?Price $price = null;

    #[\Override]
    public function getPrice(): ?Price
    {
        return $this->price;
    }

    public function setPrice(?Price $price): self
    {
        $this->price = $price;

        return $this;
    }
}
