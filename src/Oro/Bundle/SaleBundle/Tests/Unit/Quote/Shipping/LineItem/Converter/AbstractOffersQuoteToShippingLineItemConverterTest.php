<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\LineItem\Converter;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory\ShippingLineItemBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\ShippingLineItemBuilderInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Factory\ShippingLineItemCollectionFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;

abstract class AbstractOffersQuoteToShippingLineItemConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingLineItemCollectionFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $shippingLineItemCollectionFactory;

    /** @var ShippingLineItemBuilderFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $shippingLineItemBuilderFactory;

    /** @var ShippingLineItemBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $builder;

    protected function setUp(): void
    {
        $this->shippingLineItemCollectionFactory = $this->createMock(ShippingLineItemCollectionFactoryInterface::class);
        $this->shippingLineItemBuilderFactory = $this->createMock(ShippingLineItemBuilderFactoryInterface::class);
        $this->builder = $this->createMock(ShippingLineItemBuilderInterface::class);
    }

    protected function prepareConvertLineItems(
        int $quantity,
        QuoteProductDemand|\PHPUnit\Framework\MockObject\MockObject $quoteProductOffer,
        ShippingLineItemBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder
    ): DoctrineShippingLineItemCollection {
        $product = new Product();
        $productUnit = new ProductUnit();
        $productUnitCode = 'each';

        $expectedLineItem = new ShippingLineItem([
            ShippingLineItem::FIELD_PRODUCT => $product,
            ShippingLineItem::FIELD_QUANTITY => $quantity,
            ShippingLineItem::FIELD_PRODUCT_UNIT => $productUnit,
            ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => $productUnitCode,
            ShippingLineItem::FIELD_PRODUCT_HOLDER => $quoteProductOffer,
        ]);

        $expectedLineItemsArray = [$expectedLineItem];
        $expectedLineItemsCollection = new DoctrineShippingLineItemCollection($expectedLineItemsArray);

        $quoteProductOffer->expects($this->any())
            ->method('getQuantity')
            ->willReturn($quantity);
        $quoteProductOffer->expects($this->exactly(2))
            ->method('getProduct')
            ->willReturn($product);
        $quoteProductOffer->expects($this->once())
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $quoteProductOffer->expects($this->once())
            ->method('getProductUnitCode')
            ->willReturn($productUnitCode);

        $builder->expects($this->once())
            ->method('setProduct')
            ->with($product);
        $builder->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedLineItem);

        $this->shippingLineItemBuilderFactory->expects($this->once())
            ->method('createBuilder')
            ->with($productUnit, $productUnitCode, $quantity, $quoteProductOffer)
            ->willReturn($builder);

        $this->shippingLineItemCollectionFactory->expects($this->once())
            ->method('createShippingLineItemCollection')
            ->with($expectedLineItemsArray)
            ->willReturn($expectedLineItemsCollection);

        return $expectedLineItemsCollection;
    }
}
