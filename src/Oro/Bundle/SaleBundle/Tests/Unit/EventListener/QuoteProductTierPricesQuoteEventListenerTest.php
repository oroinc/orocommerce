<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\EventListener;

use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Event\QuoteEvent;
use Oro\Bundle\SaleBundle\EventListener\QuoteProductTierPricesQuoteEventListener;
use Oro\Bundle\SaleBundle\Provider\QuoteProductPricesProvider;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class QuoteProductTierPricesQuoteEventListenerTest extends TestCase
{
    private QuoteProductPricesProvider|MockObject $quoteProductPricesProvider;

    private QuoteProductTierPricesQuoteEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->quoteProductPricesProvider = $this->createMock(QuoteProductPricesProvider::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->listener = new QuoteProductTierPricesQuoteEventListener(
            $this->quoteProductPricesProvider,
            $this->authorizationChecker
        );
    }

    public function testOnQuoteEvent(): void
    {
        $quote = new Quote();
        $event = new QuoteEvent($this->createMock(FormInterface::class), $quote);

        $tierPrices = [42 => ['sample-checksum' => $this->createMock(ProductPriceInterface::class)]];

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with(
                BasicPermission::VIEW,
                'entity:' . ProductPrice::class
            )
            ->willReturn(true);

        $this->quoteProductPricesProvider
            ->expects(self::once())
            ->method('getProductLineItemsTierPrices')
            ->with($quote)
            ->willReturn($tierPrices);

        $this->listener->onQuoteEvent($event);

        self::assertSame(['tierPrices' => $tierPrices], $event->getData()->getArrayCopy());
    }

    public function testOnQuoteEventWhenProductPriceViewIsDenied(): void
    {
        $quote = new Quote();
        $event = new QuoteEvent($this->createMock(FormInterface::class), $quote);

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with(
                BasicPermission::VIEW,
                'entity:' . ProductPrice::class
            )
            ->willReturn(false);

        $this->quoteProductPricesProvider
            ->expects(self::never())
            ->method('getProductLineItemsTierPrices');

        $this->listener->onQuoteEvent($event);

        self::assertSame(
            ['tierPrices' => []],
            $event->getData()->getArrayCopy()
        );
    }
}
