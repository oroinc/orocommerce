<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\LineItem\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Oro\Bundle\ShippingBundle\Context\LineItem\Factory\ShippingLineItemFromProductLineItemFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class AbstractOffersQuoteToShippingLineItemConverterTest extends TestCase
{
    protected ShippingLineItemFromProductLineItemFactoryInterface|MockObject $shippingLineItemFactory;

    #[\Override]
    protected function setUp(): void
    {
        $this->shippingLineItemFactory = $this->createMock(ShippingLineItemFromProductLineItemFactoryInterface::class);
    }

    protected function prepareConvertLineItems(
        int $quantity,
        ?Price $price,
        QuoteProductDemand|MockObject $quoteProductOffer,
    ): ArrayCollection {
        $product = new Product();
        $productUnit = new ProductUnit();
        $productUnitCode = 'each';

        $expectedLineItem = (new ShippingLineItem(
            $productUnit,
            $quantity,
            $quoteProductOffer
        ))
            ->setProductUnitCode($productUnitCode)
            ->setProduct($product)
            ->setPrice($price);

        return new ArrayCollection([$expectedLineItem]);
    }
}
