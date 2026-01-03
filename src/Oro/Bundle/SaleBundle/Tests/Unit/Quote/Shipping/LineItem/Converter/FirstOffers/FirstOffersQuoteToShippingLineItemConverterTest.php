<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\LineItem\Converter\FirstOffers;

// phpcs:disable
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\FirstOffers\FirstOffersQuoteToShippingLineItemConverter;
use Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\LineItem\Converter\AbstractOffersQuoteToShippingLineItemConverterTest;
use PHPUnit\Framework\MockObject\MockObject;

// phpcs:enable

class FirstOffersQuoteToShippingLineItemConverterTest extends AbstractOffersQuoteToShippingLineItemConverterTest
{
    private FirstOffersQuoteToShippingLineItemConverter $firstOffersQuoteToShippingLineItemConverter;

    private QuoteProductOffer|MockObject $quoteProductOffer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->firstOffersQuoteToShippingLineItemConverter = new FirstOffersQuoteToShippingLineItemConverter(
            $this->shippingLineItemFactory
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
            $price,
            $this->quoteProductOffer
        );

        $quote = $this->getQuote($quoteProducts);

        $quoteProduct->expects(self::once())
            ->method('getQuoteProductOffers')
            ->willReturn($quoteProductOffers);

        $this->shippingLineItemFactory->expects(self::once())
            ->method('createCollection')
            ->with($quoteProductOffers->toArray())
            ->willReturn($expectedLineItemsCollection);

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
            null,
            $this->quoteProductOffer
        );

        $quote = $this->getQuote($quoteProducts);

        $this->shippingLineItemFactory->expects(self::once())
            ->method('createCollection')
            ->with($quoteProductOffers->toArray())
            ->willReturn($expectedLineItemsCollection);

        $actualLineItems = $this->firstOffersQuoteToShippingLineItemConverter->convertLineItems($quote);

        self::assertEquals($expectedLineItemsCollection, $actualLineItems);
    }

    public function testConvertLineItemsWithoutOffers(): void
    {
        $quoteProductOffers = new ArrayCollection([]);
        $quoteProduct = $this->getQuoteProduct($quoteProductOffers);
        $quoteProducts = new ArrayCollection([$quoteProduct]);

        $quote = $this->getQuote($quoteProducts);

        $this->shippingLineItemFactory->expects(self::once())
            ->method('createCollection')
            ->with([])
            ->willReturn(new ArrayCollection([]));

        $actualLineItems = $this->firstOffersQuoteToShippingLineItemConverter->convertLineItems($quote);

        self::assertEquals(new ArrayCollection([]), $actualLineItems);
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
