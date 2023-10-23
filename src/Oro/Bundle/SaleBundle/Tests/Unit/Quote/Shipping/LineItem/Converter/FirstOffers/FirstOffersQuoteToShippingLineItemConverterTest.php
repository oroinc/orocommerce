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
use PHPUnit\Framework\MockObject\MockObject;

// @codingStandardsIgnoreEnd

class FirstOffersQuoteToShippingLineItemConverterTest extends AbstractOffersQuoteToShippingLineItemConverterTest
{
    private FirstOffersQuoteToShippingLineItemConverter $firstOffersQuoteToShippingLineItemConverter;

    private QuoteProductOffer|MockObject $quoteProductOffer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->firstOffersQuoteToShippingLineItemConverter = new FirstOffersQuoteToShippingLineItemConverter(
            $this->shippingLineItemCollectionFactory,
            $this->shippingLineItemBuilderFactory
        );

        $this->quoteProductOffer = $this->createMock(QuoteProductOffer::class);
    }

    public function testConvertLineItems(): void
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

        $this->builder->expects(self::once())
            ->method('setPrice')
            ->with($price);

        $this->quoteProductOffer->expects(self::exactly(2))
            ->method('getPrice')
            ->willReturn($price);

        $quote = $this->getQuote($quoteProducts);

        $quoteProduct->expects(self::once())
            ->method('getQuoteProductOffers')
            ->willReturn($quoteProductOffers);

        $actualLineItems = $this->firstOffersQuoteToShippingLineItemConverter->convertLineItems($quote);

        self::assertEquals($expectedLineItemsCollection, $actualLineItems);
    }

    public function testConvertLineItemsWithoutPrice(): void
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

        self::assertEquals($expectedLineItemsCollection, $actualLineItems);
    }

    public function testConvertLineItemsWithoutOffers(): void
    {
        $quoteProductOffers = new ArrayCollection([]);
        $quoteProduct = $this->getQuoteProduct($quoteProductOffers);
        $quoteProducts = new ArrayCollection([$quoteProduct]);

        $expectedLineItemsArray = [];
        $expectedLineItemsCollection = new DoctrineShippingLineItemCollection($expectedLineItemsArray);

        $quote = $this->getQuote($quoteProducts);

        $this->shippingLineItemCollectionFactory->expects(self::once())
            ->method('createShippingLineItemCollection')
            ->with($expectedLineItemsArray)
            ->willReturn($expectedLineItemsCollection);

        $actualLineItems = $this->firstOffersQuoteToShippingLineItemConverter->convertLineItems($quote);

        self::assertEquals($expectedLineItemsCollection, $actualLineItems);
    }

    private function getQuoteProduct(
        Collection $quoteProductOffers
    ): QuoteProduct|MockObject {
        $quoteProduct = $this->createMock(QuoteProduct::class);
        $quoteProduct->expects(self::once())
            ->method('getQuoteProductOffers')
            ->willReturn($quoteProductOffers);

        return $quoteProduct;
    }

    private function getQuote(Collection $quoteProducts): Quote
    {
        $quote = $this->createMock(Quote::class);
        $quote->expects(self::once())
            ->method('getQuoteProducts')
            ->willReturn($quoteProducts);

        return $quote;
    }
}
