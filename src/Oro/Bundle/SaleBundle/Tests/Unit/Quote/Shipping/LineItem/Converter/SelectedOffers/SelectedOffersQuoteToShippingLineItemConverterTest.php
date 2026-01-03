<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\LineItem\Converter\SelectedOffers;

// phpcs:disable
use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\SelectedOffers\SelectedOffersQuoteToShippingLineItemConverter;
use Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\LineItem\Converter\AbstractOffersQuoteToShippingLineItemConverterTest;
use PHPUnit\Framework\MockObject\MockObject;

// phpcs:enable

class SelectedOffersQuoteToShippingLineItemConverterTest extends AbstractOffersQuoteToShippingLineItemConverterTest
{
    private QuoteProductDemand|MockObject $demandProduct;

    private QuoteDemand|MockObject $quoteDemand;

    private Quote|MockObject $quote;

    private SelectedOffersQuoteToShippingLineItemConverter $selectedOffersQuoteToShippingLineItemConverter;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->selectedOffersQuoteToShippingLineItemConverter = new SelectedOffersQuoteToShippingLineItemConverter(
            $this->shippingLineItemFactory
        );

        $this->quote = $this->createMock(Quote::class);

        $this->quoteDemand = $this->createMock(QuoteDemand::class);
        $this->demandProduct = $this->createMock(QuoteProductDemand::class);
    }

    public function testConvertItems(): void
    {
        $quantity = 12;
        $price = Price::create(12, 'USD');

        $quoteDemands = new ArrayCollection([$this->quoteDemand]);
        $demandsProducts = new ArrayCollection([$this->demandProduct]);
        $quoteProductOffer = $this->createMock(QuoteProductOffer::class);

        $expectedLineItemsCollection = $this->prepareConvertLineItems(
            $quantity,
            $price,
            $quoteProductOffer
        );

        $this->mockDemands($quoteDemands, $demandsProducts);

        $this->shippingLineItemFactory->expects(self::once())
            ->method('createCollection')
            ->with($demandsProducts->toArray())
            ->willReturn($expectedLineItemsCollection);

        $actualLineItems = $this->selectedOffersQuoteToShippingLineItemConverter->convertLineItems($this->quote);

        self::assertEquals($expectedLineItemsCollection, $actualLineItems);
    }

    public function testConvertItemsWithoutPrice(): void
    {
        $quantity = 12;

        $quoteDemands = new ArrayCollection([$this->quoteDemand]);
        $demandsProducts = new ArrayCollection([$this->demandProduct]);
        $quoteProductOffer = $this->createMock(QuoteProductOffer::class);

        $expectedLineItemsCollection = $this->prepareConvertLineItems(
            $quantity,
            null,
            $quoteProductOffer
        );

        $this->mockDemands($quoteDemands, $demandsProducts);

        $this->shippingLineItemFactory->expects(self::once())
            ->method('createCollection')
            ->with($demandsProducts->toArray())
            ->willReturn($expectedLineItemsCollection);

        $actualLineItems = $this->selectedOffersQuoteToShippingLineItemConverter->convertLineItems($this->quote);

        self::assertEquals($expectedLineItemsCollection, $actualLineItems);
    }

    public function testConvertItemsWithoutOffers(): void
    {
        $quoteDemands = new ArrayCollection([$this->quoteDemand]);
        $demandsProducts = new ArrayCollection([]);

        $this->mockDemands($quoteDemands, $demandsProducts);

        $this->shippingLineItemFactory->expects(self::once())
            ->method('createCollection')
            ->with([])
            ->willReturn(new ArrayCollection([]));

        $actualLineItems = $this->selectedOffersQuoteToShippingLineItemConverter->convertLineItems($this->quote);

        self::assertEquals(new ArrayCollection([]), $actualLineItems);
    }

    private function mockDemands(ArrayCollection $quoteDemands, ArrayCollection $demandsProducts): void
    {
        $this->quote->expects(self::once())
            ->method('getDemands')
            ->willReturn($quoteDemands);

        $this->quoteDemand->expects(self::once())
            ->method('getDemandProducts')
            ->willReturn($demandsProducts);
    }
}
