<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\LineItem\Converter;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory\ShippingLineItemBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\ShippingLineItemBuilderInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Factory\ShippingLineItemCollectionFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;

abstract class AbstractOffersQuoteToShippingLineItemConverterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ShippingLineItemCollectionFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $shippingLineItemCollectionFactory;

    /**
     * @var ShippingLineItemBuilderFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $shippingLineItemBuilderFactory;

    /**
     * @var ShippingLineItemBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $builderMock;

    protected function setUp(): void
    {
        $this->shippingLineItemCollectionFactory = $this
            ->getMockBuilder(ShippingLineItemCollectionFactoryInterface::class)
            ->getMock();

        $this->shippingLineItemBuilderFactory = $this
            ->getMockBuilder(ShippingLineItemBuilderFactoryInterface::class)
            ->getMock();

        $this->builderMock = $this
            ->getMockBuilder(ShippingLineItemBuilderInterface::class)
            ->getMock();
    }

    /**
     * @return QuoteProductOffer|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getQuoteProductOfferMock()
    {
        return $this
            ->getMockBuilder(QuoteProductOffer::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return Quote|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getQuoteMock()
    {
        return $this
            ->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param int                                                                       $quantity
     * @param QuoteProductDemand|\PHPUnit\Framework\MockObject\MockObject               $quoteProductOfferMock
     * @param ShippingLineItemBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builderMock
     *
     * @return DoctrineShippingLineItemCollection
     */
    protected function prepareConvertLineItems($quantity, $quoteProductOfferMock, $builderMock)
    {
        $product = new Product();
        $productUnit = new ProductUnit();
        $productUnitCode = 'each';

        $expectedLineItem = new ShippingLineItem([
            ShippingLineItem::FIELD_PRODUCT => $product,
            ShippingLineItem::FIELD_QUANTITY => $quantity,
            ShippingLineItem::FIELD_PRODUCT_UNIT => $productUnit,
            ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => $productUnitCode,
            ShippingLineItem::FIELD_PRODUCT_HOLDER => $quoteProductOfferMock,
        ]);

        $expectedLineItemsArray = [$expectedLineItem];
        $expectedLineItemsCollection = new DoctrineShippingLineItemCollection($expectedLineItemsArray);

        $quoteProductOfferMock
            ->expects($this->any())
            ->method('getQuantity')
            ->willReturn($quantity);

        $quoteProductOfferMock
            ->expects($this->exactly(2))
            ->method('getProduct')
            ->willReturn($product);

        $quoteProductOfferMock
            ->expects($this->once())
            ->method('getProductUnit')
            ->willReturn($productUnit);

        $quoteProductOfferMock
            ->expects($this->once())
            ->method('getProductUnitCode')
            ->willReturn($productUnitCode);

        $builderMock
            ->expects($this->once())
            ->method('setProduct')
            ->with($product);

        $builderMock
            ->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedLineItem);

        $this->shippingLineItemBuilderFactory
            ->expects($this->once())
            ->method('createBuilder')
            ->with($productUnit, $productUnitCode, $quantity, $quoteProductOfferMock)
            ->willReturn($builderMock);

        $this->shippingLineItemCollectionFactory
            ->expects($this->once())
            ->method('createShippingLineItemCollection')
            ->with($expectedLineItemsArray)
            ->willReturn($expectedLineItemsCollection);

        return $expectedLineItemsCollection;
    }

    /**
     * @param string|int $amount
     *
     * @return Price
     */
    protected function createPrice($amount)
    {
        return Price::create($amount, 'USD');
    }
}
