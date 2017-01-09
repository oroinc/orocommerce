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
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;

class FirstOffersQuoteToShippingLineItemConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FirstOffersQuoteToShippingLineItemConverter
     */
    private $firstOffersQuoteToShippingLineItemConverter;

    public function setUp()
    {
        $this->firstOffersQuoteToShippingLineItemConverter = new FirstOffersQuoteToShippingLineItemConverter();
    }

    public function testConvertLineItems()
    {
        $product = new Product();
        $productUnit = new ProductUnit();
        $quantity = 12;
        $price = Price::create(12, 'USD');

        $quoteProductMock = $this->getQuoteProductMock();
        $quoteProductOfferMock = $this->getQuoteProductOfferMock();

        $quoteProducts = new ArrayCollection([$quoteProductMock]);
        $quoteProductOffers = new ArrayCollection([$quoteProductOfferMock]);

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
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

        $quoteProductOfferMock
            ->expects($this->once())
            ->method('getProductUnit')
            ->willReturn($productUnit);

        $quoteProductOfferMock
            ->expects($this->once())
            ->method('getQuantity')
            ->willReturn($quantity);

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

        $actualLineItems = $this->firstOffersQuoteToShippingLineItemConverter->convertLineItems($quoteMock);

        $this->assertEquals($expectedLineItems, $actualLineItems);
    }

    public function testConvertLineItemsWithoutOffers()
    {
        $quoteProductMock = $this->getQuoteProductMock();

        $quoteProducts = new ArrayCollection([$quoteProductMock]);
        $quoteProductOffers = new ArrayCollection([]);

        $quoteMock = $this->getQuoteMock();
        $quoteMock
            ->expects($this->once())
            ->method('getQuoteProducts')
            ->willReturn($quoteProducts);

        $quoteProductMock
            ->expects($this->once())
            ->method('getQuoteProductOffers')
            ->willReturn($quoteProductOffers);

        $expectedLineItems = [];

        $actualLineItems = $this->firstOffersQuoteToShippingLineItemConverter->convertLineItems($quoteMock);

        $this->assertEquals($expectedLineItems, $actualLineItems);
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
