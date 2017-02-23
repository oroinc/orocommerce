<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\LineItem\Converter\FirstOffers;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\FirstOffers\FirstOffersQuoteToShippingLineItemConverter;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
// @codingStandardsIgnoreStart
use Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\LineItem\Converter\AbstractOffersQuoteToShippingLineItemConverterTest;

// @codingStandardsIgnoreEnd

class FirstOffersQuoteToShippingLineItemConverterTest extends AbstractOffersQuoteToShippingLineItemConverterTest
{
    /**
     * @var FirstOffersQuoteToShippingLineItemConverter
     */
    protected $firstOffersQuoteToShippingLineItemConverter;

    public function setUp()
    {
        parent::setUp();
        $this->firstOffersQuoteToShippingLineItemConverter = new FirstOffersQuoteToShippingLineItemConverter(
            $this->shippingLineItemCollectionFactory,
            $this->shippingLineItemBuilderFactory
        );
    }

    public function testConvertLineItems()
    {
        $quantity = 12;

        $quoteProductMock = $this->getQuoteProductMock();
        $quoteProductOfferMock = $this->getQuoteProductOfferMock();
        $quoteProducts = new ArrayCollection([$quoteProductMock]);
        $quoteProductOffers = new ArrayCollection([$quoteProductOfferMock]);

        $expectedLineItemsCollection = $this->prepareConvertLineItems($quantity, $quoteProductOfferMock);

        $quoteProductOfferMock
            ->expects($this->once())
            ->method('getQuantity')
            ->willReturn($quantity);

        $quoteMock = $this->getQuoteMock();
        $quoteMock
            ->expects($this->once())
            ->method('getQuoteProducts')
            ->willReturn($quoteProducts);

        $quoteProductMock
            ->expects($this->once())
            ->method('getQuoteProductOffers')
            ->willReturn($quoteProductOffers);

        $actualLineItems = $this->firstOffersQuoteToShippingLineItemConverter->convertLineItems($quoteMock);

        $this->assertEquals($expectedLineItemsCollection, $actualLineItems);
    }

    public function testConvertLineItemsWithoutPrice()
    {
        $quoteProductOfferMock = $this->getQuoteProductOfferMock();
        $quoteProductOffers = new ArrayCollection([$quoteProductOfferMock]);

        $quoteProductMock = $this->getQuoteProductMock();
        $quoteProductMock->expects($this->once())->method('getQuoteProductOffers')->willReturn($quoteProductOffers);

        $expectedLineItemsArray = [];
        $expectedLineItemsCollection = new DoctrineShippingLineItemCollection($expectedLineItemsArray);

        $quoteMock = $this->getQuoteMock();
        $quoteMock->expects($this->once())
            ->method('getQuoteProducts')
            ->willReturn(new ArrayCollection([$quoteProductMock]));

        $this->shippingLineItemBuilderFactory->expects($this->never())->method('createBuilder');

        $this->shippingLineItemCollectionFactory
            ->expects($this->once())
            ->method('createShippingLineItemCollection')
            ->with($expectedLineItemsArray)
            ->willReturn($expectedLineItemsCollection);

        $this->assertEquals(
            $expectedLineItemsCollection,
            $this->firstOffersQuoteToShippingLineItemConverter->convertLineItems($quoteMock)
        );
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
     * @return QuoteProduct|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getQuoteProductMock()
    {
        return $this
            ->getMockBuilder(QuoteProduct::class)
            ->getMock();
    }
}
