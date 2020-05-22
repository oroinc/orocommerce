<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\LineItem\Converter\FirstOffers;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\FirstOffers\FirstOffersQuoteToShippingLineItemConverter;
// @codingStandardsIgnoreStart
use Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\LineItem\Converter\AbstractOffersQuoteToShippingLineItemConverterTest;
// @codingStandardsIgnoreEnd
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;

class FirstOffersQuoteToShippingLineItemConverterTest extends AbstractOffersQuoteToShippingLineItemConverterTest
{
    /**
     * @var FirstOffersQuoteToShippingLineItemConverter
     */
    protected $firstOffersQuoteToShippingLineItemConverter;

    /**
     * @var QuoteProductOffer|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $quoteProductOfferMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->firstOffersQuoteToShippingLineItemConverter = new FirstOffersQuoteToShippingLineItemConverter(
            $this->shippingLineItemCollectionFactory,
            $this->shippingLineItemBuilderFactory
        );

        $this->quoteProductOfferMock = $this->getQuoteProductOfferMock();
    }

    public function testConvertLineItems()
    {
        $quantity = 12;
        $price = $this->createPrice(12);

        $quoteProductOffers = new ArrayCollection([$this->quoteProductOfferMock]);
        $quoteProductMock = $this->getQuoteProductMock($quoteProductOffers);
        $quoteProducts = new ArrayCollection([$quoteProductMock]);

        $expectedLineItemsCollection = $this
            ->prepareConvertLineItems($quantity, $this->quoteProductOfferMock, $this->builderMock);

        $this->builderMock
            ->expects($this->once())
            ->method('setPrice')
            ->with($price);

        $this->quoteProductOfferMock
            ->expects($this->exactly(2))
            ->method('getPrice')
            ->willReturn($price);

        $quoteMock = $this->mockQuote($quoteProducts);

        $quoteProductMock
            ->expects($this->once())
            ->method('getQuoteProductOffers')
            ->willReturn($quoteProductOffers);

        $actualLineItems = $this->firstOffersQuoteToShippingLineItemConverter->convertLineItems($quoteMock);

        $this->assertEquals($expectedLineItemsCollection, $actualLineItems);
    }

    public function testConvertLineItemsWithoutPrice()
    {
        $quantity = 12;

        $quoteProductOffers = new ArrayCollection([$this->quoteProductOfferMock]);
        $quoteProductMock = $this->getQuoteProductMock($quoteProductOffers);
        $quoteProducts = new ArrayCollection([$quoteProductMock]);

        $expectedLineItemsCollection = $this
            ->prepareConvertLineItems($quantity, $this->quoteProductOfferMock, $this->builderMock);

        $quoteMock = $this->mockQuote($quoteProducts);

        $actualLineItems = $this->firstOffersQuoteToShippingLineItemConverter->convertLineItems($quoteMock);

        $this->assertEquals($expectedLineItemsCollection, $actualLineItems);
    }

    public function testConvertLineItemsWithoutOffers()
    {
        $quoteProductOffers = new ArrayCollection([]);
        $quoteProductMock = $this->getQuoteProductMock($quoteProductOffers);
        $quoteProducts = new ArrayCollection([$quoteProductMock]);

        $expectedLineItemsArray = [];
        $expectedLineItemsCollection = new DoctrineShippingLineItemCollection($expectedLineItemsArray);

        $quoteMock = $this->mockQuote($quoteProducts);

        $this->shippingLineItemCollectionFactory
            ->expects($this->once())
            ->method('createShippingLineItemCollection')
            ->with($expectedLineItemsArray)
            ->willReturn($expectedLineItemsCollection);

        $actualLineItems = $this->firstOffersQuoteToShippingLineItemConverter->convertLineItems($quoteMock);

        $this->assertEquals($expectedLineItemsCollection, $actualLineItems);
    }

    /**
     * @param Collection $quoteProductOffers
     *
     * @return QuoteProduct|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getQuoteProductMock(Collection $quoteProductOffers)
    {
        $quoteProductMock = $this
            ->getMockBuilder(QuoteProduct::class)
            ->getMock();

        $quoteProductMock
            ->expects($this->once())
            ->method('getQuoteProductOffers')
            ->willReturn($quoteProductOffers);

        return $quoteProductMock;
    }

    /**
     * @param Collection $quoteProducts
     *
     * @return \Oro\Bundle\SaleBundle\Entity\Quote|\PHPUnit\Framework\MockObject\MockObject
     */
    private function mockQuote(Collection $quoteProducts)
    {
        $quoteMock = $this->getQuoteMock();
        $quoteMock
            ->expects($this->once())
            ->method('getQuoteProducts')
            ->willReturn($quoteProducts);

        return $quoteMock;
    }
}
