<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\LineItem\Converter\SelectedOffers;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
// @codingStandardsIgnoreStart
use Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\SelectedOffers\SelectedOffersQuoteToShippingLineItemConverter;
use Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\LineItem\Converter\AbstractOffersQuoteToShippingLineItemConverterTest;
// @codingStandardsIgnoreEnd

class SelectedOffersQuoteToShippingLineItemConverterTest extends AbstractOffersQuoteToShippingLineItemConverterTest
{
    /**
     * @var SelectedOffersQuoteToShippingLineItemConverter
     */
    private $selectedOffersQuoteToShippingLineItemConverter;

    public function setUp()
    {
        parent::setUp();
        $this->selectedOffersQuoteToShippingLineItemConverter = new SelectedOffersQuoteToShippingLineItemConverter(
            $this->shippingLineItemCollectionFactory,
            $this->shippingLineItemBuilderFactory
        );
    }


    public function testConvertItems()
    {
        $quantity = 12;

        $quoteMock = $this->getQuoteMock();
        $quoteDemand = $this->getQuoteDemandMock();
        $quoteDemands = new ArrayCollection([$quoteDemand]);
        $demandProduct = $this->getQuoteProductDemandMock();
        $demandsProducts = new ArrayCollection([$demandProduct]);
        $quoteProductOfferMock = $this->getQuoteProductOfferMock();

        $expectedLineItemsCollection = $this->prepareConvertLineItems($quantity, $quoteProductOfferMock);

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
}
