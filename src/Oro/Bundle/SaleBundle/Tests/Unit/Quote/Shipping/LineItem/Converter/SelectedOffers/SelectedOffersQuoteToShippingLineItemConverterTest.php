<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\LineItem\Converter\SelectedOffers;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
// @codingStandardsIgnoreStart
use Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\SelectedOffers\SelectedOffersQuoteToShippingLineItemConverter;
use Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\LineItem\Converter\AbstractOffersQuoteToShippingLineItemConverterTest;
// @codingStandardsIgnoreEnd
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;

class SelectedOffersQuoteToShippingLineItemConverterTest extends AbstractOffersQuoteToShippingLineItemConverterTest
{
    /**
     * @var QuoteProductDemand|\PHPUnit\Framework\MockObject\MockObject
     */
    private $demandProduct;

    /**
     * @var QuoteDemand|\PHPUnit\Framework\MockObject\MockObject
     */
    private $quoteDemand;

    /**
     * @var Quote|\PHPUnit\Framework\MockObject\MockObject
     */
    private $quoteMock;

    /**
     * @var SelectedOffersQuoteToShippingLineItemConverter
     */
    private $selectedOffersQuoteToShippingLineItemConverter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->selectedOffersQuoteToShippingLineItemConverter = new SelectedOffersQuoteToShippingLineItemConverter(
            $this->shippingLineItemCollectionFactory,
            $this->shippingLineItemBuilderFactory
        );

        $this->quoteMock = $this->getQuoteMock();

        $this->quoteDemand = $this
            ->getMockBuilder(QuoteDemand::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->demandProduct = $this
            ->getMockBuilder(QuoteProductDemand::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testConvertItems()
    {
        $quantity = 12;
        $price = $this->createPrice(12);

        $quoteDemands = new ArrayCollection([$this->quoteDemand]);
        $demandsProducts = new ArrayCollection([$this->demandProduct]);
        $quoteProductOfferMock = $this->getQuoteProductOfferMock();

        $expectedLineItemsCollection = $this
            ->prepareConvertLineItems($quantity, $quoteProductOfferMock, $this->builderMock);

        $this->builderMock
            ->expects($this->once())
            ->method('setPrice')
            ->with($price);

        $quoteProductOfferMock
            ->expects($this->exactly(2))
            ->method('getPrice')
            ->willReturn($price);

        $this->mockDemands($quoteDemands, $demandsProducts);
        $this->mockDemandProduct($quantity, $quoteProductOfferMock);

        $actualLineItems = $this->selectedOffersQuoteToShippingLineItemConverter->convertLineItems($this->quoteMock);

        $this->assertEquals($expectedLineItemsCollection, $actualLineItems);
    }

    public function testConvertItemsWithoutPrice()
    {
        $quantity = 12;

        $quoteDemands = new ArrayCollection([$this->quoteDemand]);
        $demandsProducts = new ArrayCollection([$this->demandProduct]);
        $quoteProductOfferMock = $this->getQuoteProductOfferMock();

        $expectedLineItemsCollection = $this
            ->prepareConvertLineItems($quantity, $quoteProductOfferMock, $this->builderMock);

        $this->mockDemands($quoteDemands, $demandsProducts);
        $this->mockDemandProduct($quantity, $quoteProductOfferMock);

        $actualLineItems = $this->selectedOffersQuoteToShippingLineItemConverter->convertLineItems($this->quoteMock);

        $this->assertEquals($expectedLineItemsCollection, $actualLineItems);
    }

    public function testConvertItemsWithoutOffers()
    {
        $quoteDemands = new ArrayCollection([$this->quoteDemand]);
        $demandsProducts = new ArrayCollection([]);
        $expectedLineItemsCollection = new DoctrineShippingLineItemCollection([]);

        $this->mockDemands($quoteDemands, $demandsProducts);

        $this->demandProduct
            ->expects($this->never())
            ->method('getQuantity');

        $this->demandProduct
            ->expects($this->never())
            ->method('getQuoteProductOffer');

        $this->shippingLineItemCollectionFactory
            ->expects($this->once())
            ->method('createShippingLineItemCollection')
            ->with([])
            ->willReturn($expectedLineItemsCollection);

        $actualLineItems = $this->selectedOffersQuoteToShippingLineItemConverter->convertLineItems($this->quoteMock);

        $this->assertEquals($expectedLineItemsCollection, $actualLineItems);
    }

    /**
     * @param int                                                        $quantity
     * @param QuoteProductOffer|\PHPUnit\Framework\MockObject\MockObject $quoteProductOfferMock
     */
    private function mockDemandProduct($quantity, $quoteProductOfferMock)
    {
        $this->demandProduct
            ->expects($this->once())
            ->method('getQuantity')
            ->willReturn($quantity);

        $this->demandProduct
            ->expects($this->once())
            ->method('getQuoteProductOffer')
            ->willReturn($quoteProductOfferMock);
    }

    /**
     * @param ArrayCollection $quoteDemands
     * @param ArrayCollection $demandsProducts
     */
    private function mockDemands($quoteDemands, $demandsProducts)
    {
        $this->quoteMock
            ->expects($this->once())
            ->method('getDemands')
            ->willReturn($quoteDemands);

        $this->quoteDemand
            ->expects($this->once())
            ->method('getDemandProducts')
            ->willReturn($demandsProducts);
    }
}
