<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\LineItem\Converter\SelectedOffers;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory\ShippingLineItemBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\ShippingLineItemBuilderInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Factory\ShippingLineItemCollectionFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
// @codingStandardsIgnoreStart
use Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\SelectedOffers\SelectedOffersQuoteToShippingLineItemConverter;
// @codingStandardsIgnoreEnd

class SelectedOffersQuoteToShippingLineItemConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SelectedOffersQuoteToShippingLineItemConverter
     */
    private $selectedOffersQuoteToShippingLineItemConverter;

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

        $this->selectedOffersQuoteToShippingLineItemConverter = new SelectedOffersQuoteToShippingLineItemConverter(
            $this->shippingLineItemCollectionFactory,
            $this->shippingLineItemBuilderFactory
        );
    }


    public function testConvertItems()
    {
        $product = new Product();
        $productUnit = new ProductUnit();
        $productUnitCode = 'each';
        $quantity = 12;
        $price = Price::create(12, 'USD');

        $quoteMock = $this->getQuoteMock();
        $quoteDemand = $this->getQuoteDemandMock();
        $quoteDemands = new ArrayCollection([$quoteDemand]);
        $demandProduct = $this->getQuoteProductDemandMock();
        $demandsProducts = new ArrayCollection([$demandProduct]);
        $quoteProductOfferMock = $this->getQuoteProductOfferMock();
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

        $quoteMock
            ->expects($this->once())
            ->method('getDemands')
            ->willReturn($quoteDemands);

        $quoteDemand
            ->expects($this->once())
            ->method('getDemandProducts')
            ->willReturn($demandsProducts);

        $demandProduct
            ->expects($this->once())
            ->method('getQuantity')
            ->willReturn($quantity);

        $demandProduct
            ->expects($this->once())
            ->method('getQuoteProductOffer')
            ->willReturn($quoteProductOfferMock);

        $quoteProductOfferMock
            ->expects($this->once())
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

        $actualLineItems = $this->selectedOffersQuoteToShippingLineItemConverter->convertLineItems($quoteMock);

        $this->assertEquals($expectedLineItemsCollection, $actualLineItems);
    }

    public function testConvertItemsWithoutOffers()
    {
        $quoteMock = $this->getQuoteMock();

        $quoteDemand = $this->getQuoteDemandMock();
        $quoteDemands = new ArrayCollection([$quoteDemand]);
        $demandProduct = $this->getQuoteProductDemandMock();
        $demandsProducts = new ArrayCollection([]);
        $expectedLineItemsCollection = new DoctrineShippingLineItemCollection([]);

        $quoteMock
            ->expects($this->once())
            ->method('getDemands')
            ->willReturn($quoteDemands);

        $quoteDemand
            ->expects($this->once())
            ->method('getDemandProducts')
            ->willReturn($demandsProducts);

        $demandProduct
            ->expects($this->never())
            ->method('getQuantity');

        $demandProduct
            ->expects($this->never())
            ->method('getQuoteProductOffer');

        $this->shippingLineItemCollectionFactory
            ->expects($this->once())
            ->method('createShippingLineItemCollection')
            ->with([])
            ->willReturn($expectedLineItemsCollection);

        $actualLineItems = $this->selectedOffersQuoteToShippingLineItemConverter->convertLineItems($quoteMock);

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
     * @return QuoteProductDemand|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getQuoteProductDemandMock()
    {
        return $this
            ->getMockBuilder(QuoteProductDemand::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return QuoteDemand|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getQuoteDemandMock()
    {
        return $this
            ->getMockBuilder(QuoteDemand::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return QuoteProductOffer|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getQuoteProductOfferMock()
    {
        return $this
            ->getMockBuilder(QuoteProductOffer::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getQuoteMock()
    {
        return $this
            ->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
