<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\LineItem\Converter\FirstOffers;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\FirstOffers\FirstOffersQuoteToShippingLineItemConverter;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory\ShippingLineItemBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\ShippingLineItemBuilderInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Factory\ShippingLineItemCollectionFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;

class FirstOffersQuoteToShippingLineItemConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FirstOffersQuoteToShippingLineItemConverter
     */
    private $firstOffersQuoteToShippingLineItemConverter;

    /**
     * @var ShippingLineItemCollectionFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingLineItemCollectionFactory;

    /**
     * @var ShippingLineItemBuilderFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingLineItemBuilderFactory;

    public function setUp()
    {
        $this->shippingLineItemCollectionFactory = $this
            ->getMockBuilder(ShippingLineItemCollectionFactoryInterface::class)
            ->getMock();

        $this->shippingLineItemBuilderFactory = $this
            ->getMockBuilder(ShippingLineItemBuilderFactoryInterface::class)
            ->getMock();

        $this->firstOffersQuoteToShippingLineItemConverter = new FirstOffersQuoteToShippingLineItemConverter(
            $this->shippingLineItemCollectionFactory,
            $this->shippingLineItemBuilderFactory
        );
    }

    public function testConvertLineItems()
    {
        $product = new Product();
        $productUnit = new ProductUnit();
        $productUnitCode = 'each';
        $quantity = 12;
        $price = Price::create(12, 'USD');

        $quoteProductMock = $this->getQuoteProductMock();
        $quoteProductOfferMock = $this->getQuoteProductOfferMock();
        $quoteProducts = new ArrayCollection([$quoteProductMock]);
        $quoteProductOffers = new ArrayCollection([$quoteProductOfferMock]);
        $builderMock = $this->getShippingLineItemBuilderMock();

        $expectedLineItem = new ShippingLineItem([
            ShippingLineItem::FIELD_PRODUCT => $product,
            ShippingLineItem::FIELD_QUANTITY => $quantity,
            ShippingLineItem::FIELD_PRODUCT_UNIT => $productUnit,
            ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => $productUnitCode,
            ShippingLineItem::FIELD_PRODUCT_HOLDER => $quoteProductOfferMock,
            ShippingLineItem::FIELD_PRICE => $price
        ]);

        $expectedLineItemsArray = [$expectedLineItem];
        $expectedLineItemsCollection = new DoctrineShippingLineItemCollection($expectedLineItemsArray);

        $quoteMock = $this->getQuoteMock();
        $quoteMock
            ->expects($this->once())
            ->method('getQuoteProducts')
            ->willReturn($quoteProducts);

        $quoteProductMock
            ->expects($this->exactly(2))
            ->method('getQuoteProductOffers')
            ->willReturn($quoteProductOffers);

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

        $quoteProductOfferMock
            ->expects($this->once())
            ->method('getQuantity')
            ->willReturn($quantity);

        $quoteProductOfferMock
            ->expects($this->once())
            ->method('getPrice')
            ->willReturn($price);

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
            ->with($price, $productUnit, $productUnitCode, $quantity, $quoteProductOfferMock)
            ->willReturn($builderMock);

        $this->shippingLineItemCollectionFactory
            ->expects($this->once())
            ->method('createShippingLineItemCollection')
            ->with($expectedLineItemsArray)
            ->willReturn($expectedLineItemsCollection);

        $actualLineItems = $this->firstOffersQuoteToShippingLineItemConverter->convertLineItems($quoteMock);

        $this->assertEquals($expectedLineItemsCollection, $actualLineItems);
    }

    public function testConvertLineItemsWithoutOffers()
    {
        $quoteProductMock = $this->getQuoteProductMock();

        $quoteProducts = new ArrayCollection([$quoteProductMock]);
        $quoteProductOffers = new ArrayCollection([]);
        $expectedLineItemsArray = [];
        $expectedLineItemsCollection = new DoctrineShippingLineItemCollection($expectedLineItemsArray);

        $quoteMock = $this->getQuoteMock();
        $quoteMock
            ->expects($this->once())
            ->method('getQuoteProducts')
            ->willReturn($quoteProducts);

        $quoteProductMock
            ->expects($this->once())
            ->method('getQuoteProductOffers')
            ->willReturn($quoteProductOffers);

        $this->shippingLineItemCollectionFactory
            ->expects($this->once())
            ->method('createShippingLineItemCollection')
            ->with($expectedLineItemsArray)
            ->willReturn($expectedLineItemsCollection);

        $actualLineItems = $this->firstOffersQuoteToShippingLineItemConverter->convertLineItems($quoteMock);

        $this->assertEquals($expectedLineItemsCollection, $actualLineItems);
    }

    /**
     * @return ShippingLineItemBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getShippingLineItemBuilderMock()
    {
        return $this
            ->getMockBuilder(ShippingLineItemBuilderInterface::class)
            ->getMock();
    }

    /**
     * @return QuoteProductOffer|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getQuoteProductOfferMock()
    {
        return $this
            ->getMockBuilder(QuoteProductOffer::class)
            ->getMock();
    }

    /**
     * @return QuoteProduct|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getQuoteProductMock()
    {
        return $this
            ->getMockBuilder(QuoteProduct::class)
            ->getMock();
    }

    /**
     * @return Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getQuoteMock()
    {
        return $this
            ->getMockBuilder(Quote::class)
            ->getMock();
    }
}
