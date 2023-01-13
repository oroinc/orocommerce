<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\LineItem\Converter\FirstOffers;

// @codingStandardsIgnoreStart
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\FirstOffers\FirstOffersQuoteToShippingLineItemConverter;
use Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\LineItem\Converter\AbstractOffersQuoteToShippingLineItemConverterTest;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;

// @codingStandardsIgnoreEnd

class FirstOffersQuoteToShippingLineItemConverterTest extends AbstractOffersQuoteToShippingLineItemConverterTest
{
    /** @var FirstOffersQuoteToShippingLineItemConverter */
    private $firstOffersQuoteToShippingLineItemConverter;

    /** @var QuoteProductOffer|\PHPUnit\Framework\MockObject\MockObject */
    private $quoteProductOffer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->firstOffersQuoteToShippingLineItemConverter = new FirstOffersQuoteToShippingLineItemConverter(
            $this->shippingLineItemCollectionFactory,
            $this->shippingLineItemBuilderFactory
        );

        $this->quoteProductOffer = $this->createMock(QuoteProductOffer::class);
    }

    public function testConvertLineItems()
    {
        $quantity = 12;
        $price = Price::create(12, 'USD');

        $quoteProductOffers = new ArrayCollection([$this->quoteProductOffer]);
        $quoteProduct = $this->getQuoteProduct($quoteProductOffers);
        $quoteProducts = new ArrayCollection([$quoteProduct]);

        $expectedLineItemsCollection = $this->prepareConvertLineItems(
            $quantity,
            $this->quoteProductOffer,
            $this->builder
        );

        $this->builder->expects($this->once())
            ->method('setPrice')
            ->with($price);

        $this->quoteProductOffer->expects($this->exactly(2))
            ->method('getPrice')
            ->willReturn($price);

        $quote = $this->getQuote($quoteProducts);

        $quoteProduct->expects($this->once())
            ->method('getQuoteProductOffers')
            ->willReturn($quoteProductOffers);

        $actualLineItems = $this->firstOffersQuoteToShippingLineItemConverter->convertLineItems($quote);

        $this->assertEquals($expectedLineItemsCollection, $actualLineItems);
    }

    public function testConvertLineItemsWithoutPrice()
    {
        $quantity = 12;

        $quoteProductOffers = new ArrayCollection([$this->quoteProductOffer]);
        $quoteProduct = $this->getQuoteProduct($quoteProductOffers);
        $quoteProducts = new ArrayCollection([$quoteProduct]);

        $expectedLineItemsCollection = $this->prepareConvertLineItems(
            $quantity,
            $this->quoteProductOffer,
            $this->builder
        );

        $quote = $this->getQuote($quoteProducts);

        $actualLineItems = $this->firstOffersQuoteToShippingLineItemConverter->convertLineItems($quote);

        $this->assertEquals($expectedLineItemsCollection, $actualLineItems);
    }

    public function testConvertLineItemsWithoutOffers()
    {
        $quoteProductOffers = new ArrayCollection([]);
        $quoteProduct = $this->getQuoteProduct($quoteProductOffers);
        $quoteProducts = new ArrayCollection([$quoteProduct]);

        $expectedLineItemsArray = [];
        $expectedLineItemsCollection = new DoctrineShippingLineItemCollection($expectedLineItemsArray);

        $quote = $this->getQuote($quoteProducts);

        $this->shippingLineItemCollectionFactory->expects($this->once())
            ->method('createShippingLineItemCollection')
            ->with($expectedLineItemsArray)
            ->willReturn($expectedLineItemsCollection);

        $actualLineItems = $this->firstOffersQuoteToShippingLineItemConverter->convertLineItems($quote);

        $this->assertEquals($expectedLineItemsCollection, $actualLineItems);
    }

    private function getQuoteProduct(
        Collection $quoteProductOffers
    ): QuoteProduct|\PHPUnit\Framework\MockObject\MockObject {
        $quoteProduct = $this->createMock(QuoteProduct::class);
        $quoteProduct->expects($this->once())
            ->method('getQuoteProductOffers')
            ->willReturn($quoteProductOffers);

        return $quoteProduct;
    }

    private function getQuote(Collection $quoteProducts): Quote
    {
        $quote = $this->createMock(Quote::class);
        $quote->expects($this->once())
            ->method('getQuoteProducts')
            ->willReturn($quoteProducts);

        return $quote;
    }
}
