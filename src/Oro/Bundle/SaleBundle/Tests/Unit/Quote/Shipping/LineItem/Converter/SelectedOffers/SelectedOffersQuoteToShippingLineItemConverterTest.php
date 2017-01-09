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

    public function setUp()
    {
        $this->selectedOffersQuoteToShippingLineItemConverter = new SelectedOffersQuoteToShippingLineItemConverter();
    }

    public function testConvertItems()
    {
        $product = new Product();
        $productUnit = new ProductUnit();
        $quantity = 12;
        $price = Price::create(12, 'USD');

        $quoteMock = $this->getQuoteMock();

        $quoteDemand = $this->getQuoteDemandMock();
        $quoteDemands = new ArrayCollection([$quoteDemand]);
        $demandProduct = $this->getQuoteProductDemandMock();
        $demandsProducts = new ArrayCollection([$demandProduct]);
        $quoteProductOfferMock = $this->getQuoteProductOfferMock();

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
            ->method('getPrice')
            ->willReturn($price);

        $expectedLineItems = [
            (new ShippingLineItem())
                ->setProduct($product)
                ->setQuantity($quantity)
                ->setProductUnit($productUnit)
                ->setProductHolder($quoteProductOfferMock)
                ->setPrice($price),
        ];

        $actualLineItems = $this->selectedOffersQuoteToShippingLineItemConverter->convertLineItems($quoteMock);

        $this->assertEquals($expectedLineItems, $actualLineItems);
    }

    public function testConvertItemsWithoutOffers()
    {
        $quoteMock = $this->getQuoteMock();

        $quoteDemand = $this->getQuoteDemandMock();
        $quoteDemands = new ArrayCollection([$quoteDemand]);
        $demandProduct = $this->getQuoteProductDemandMock();
        $demandsProducts = new ArrayCollection([]);

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

        $expectedLineItems = [];

        $actualLineItems = $this->selectedOffersQuoteToShippingLineItemConverter->convertLineItems($quoteMock);

        $this->assertEquals($expectedLineItems, $actualLineItems);
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
