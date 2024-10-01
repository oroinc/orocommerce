<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\EventListener;

use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Event\QuoteEvent;
use Oro\Bundle\SaleBundle\EventListener\QuoteProductTierPricesQuoteEventListener;
use Oro\Bundle\SaleBundle\Provider\QuoteProductPricesProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;

class QuoteProductTierPricesQuoteEventListenerTest extends TestCase
{
    private QuoteProductPricesProvider|MockObject $quoteProductPricesProvider;

    private QuoteProductTierPricesQuoteEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->quoteProductPricesProvider = $this->createMock(QuoteProductPricesProvider::class);
        $this->listener = new QuoteProductTierPricesQuoteEventListener($this->quoteProductPricesProvider);
    }

    public function testOnQuoteEvent(): void
    {
        $quote = new Quote();
        $event = new QuoteEvent($this->createMock(FormInterface::class), $quote);

        $tierPrices = [42 => ['sample-checksum' => $this->createMock(ProductPriceInterface::class)]];
        $this->quoteProductPricesProvider
            ->expects(self::once())
            ->method('getProductLineItemsTierPrices')
            ->with($quote)
            ->willReturn($tierPrices);

        $this->listener->onQuoteEvent($event);

        self::assertSame(['tierPrices' => $tierPrices], $event->getData()->getArrayCopy());
    }
}
