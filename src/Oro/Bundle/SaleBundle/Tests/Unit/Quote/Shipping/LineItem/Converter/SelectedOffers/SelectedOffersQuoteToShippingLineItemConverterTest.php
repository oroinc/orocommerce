<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\LineItem\Converter\SelectedOffers;

// @codingStandardsIgnoreStart
use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\SelectedOffers\SelectedOffersQuoteToShippingLineItemConverter;
use Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\LineItem\Converter\AbstractOffersQuoteToShippingLineItemConverterTest;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;

// @codingStandardsIgnoreEnd

class SelectedOffersQuoteToShippingLineItemConverterTest extends AbstractOffersQuoteToShippingLineItemConverterTest
{
    /** @var QuoteProductDemand|\PHPUnit\Framework\MockObject\MockObject */
    private $demandProduct;

    /** @var QuoteDemand|\PHPUnit\Framework\MockObject\MockObject */
    private $quoteDemand;

    /** @var Quote|\PHPUnit\Framework\MockObject\MockObject */
    private $quote;

    /** @var SelectedOffersQuoteToShippingLineItemConverter */
    private $selectedOffersQuoteToShippingLineItemConverter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->selectedOffersQuoteToShippingLineItemConverter = new SelectedOffersQuoteToShippingLineItemConverter(
            $this->shippingLineItemCollectionFactory,
            $this->shippingLineItemBuilderFactory
        );

        $this->quote = $this->createMock(Quote::class);

        $this->quoteDemand = $this->createMock(QuoteDemand::class);
        $this->demandProduct = $this->createMock(QuoteProductDemand::class);
    }

    public function testConvertItems()
    {
        $quantity = 12;
        $price = Price::create(12, 'USD');

        $quoteDemands = new ArrayCollection([$this->quoteDemand]);
        $demandsProducts = new ArrayCollection([$this->demandProduct]);
        $quoteProductOffer = $this->createMock(QuoteProductOffer::class);

        $expectedLineItemsCollection = $this->prepareConvertLineItems(
            $quantity,
            $quoteProductOffer,
            $this->builder
        );

        $this->builder->expects($this->once())
            ->method('setPrice')
            ->with($price);

        $quoteProductOffer->expects($this->exactly(2))
            ->method('getPrice')
            ->willReturn($price);

        $this->mockDemands($quoteDemands, $demandsProducts);
        $this->mockDemandProduct($quantity, $quoteProductOffer);

        $actualLineItems = $this->selectedOffersQuoteToShippingLineItemConverter->convertLineItems($this->quote);

        $this->assertEquals($expectedLineItemsCollection, $actualLineItems);
    }

    public function testConvertItemsWithoutPrice()
    {
        $quantity = 12;

        $quoteDemands = new ArrayCollection([$this->quoteDemand]);
        $demandsProducts = new ArrayCollection([$this->demandProduct]);
        $quoteProductOffer = $this->createMock(QuoteProductOffer::class);

        $expectedLineItemsCollection = $this->prepareConvertLineItems(
            $quantity,
            $quoteProductOffer,
            $this->builder
        );

        $this->mockDemands($quoteDemands, $demandsProducts);
        $this->mockDemandProduct($quantity, $quoteProductOffer);

        $actualLineItems = $this->selectedOffersQuoteToShippingLineItemConverter->convertLineItems($this->quote);

        $this->assertEquals($expectedLineItemsCollection, $actualLineItems);
    }

    public function testConvertItemsWithoutOffers()
    {
        $quoteDemands = new ArrayCollection([$this->quoteDemand]);
        $demandsProducts = new ArrayCollection([]);
        $expectedLineItemsCollection = new DoctrineShippingLineItemCollection([]);

        $this->mockDemands($quoteDemands, $demandsProducts);

        $this->demandProduct->expects($this->never())
            ->method('getQuantity');

        $this->demandProduct->expects($this->never())
            ->method('getQuoteProductOffer');

        $this->shippingLineItemCollectionFactory->expects($this->once())
            ->method('createShippingLineItemCollection')
            ->with([])
            ->willReturn($expectedLineItemsCollection);

        $actualLineItems = $this->selectedOffersQuoteToShippingLineItemConverter->convertLineItems($this->quote);

        $this->assertEquals($expectedLineItemsCollection, $actualLineItems);
    }

    private function mockDemandProduct(int $quantity, QuoteProductOffer $quoteProductOffer): void
    {
        $this->demandProduct->expects($this->once())
            ->method('getQuantity')
            ->willReturn($quantity);
        $this->demandProduct->expects($this->once())
            ->method('getQuoteProductOffer')
            ->willReturn($quoteProductOffer);
    }

    private function mockDemands(ArrayCollection $quoteDemands, ArrayCollection $demandsProducts): void
    {
        $this->quote->expects($this->once())
            ->method('getDemands')
            ->willReturn($quoteDemands);

        $this->quoteDemand->expects($this->once())
            ->method('getDemandProducts')
            ->willReturn($demandsProducts);
    }
}
