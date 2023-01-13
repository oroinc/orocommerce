<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\EventListener\Order\MatchingPriceEventListener;
use Oro\Bundle\OrderBundle\Pricing\PriceMatcher;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormInterface;

class MatchingPriceEventListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var PriceMatcher|\PHPUnit\Framework\MockObject\MockObject */
    private $priceMatcher;

    /** @var MatchingPriceEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->priceMatcher = $this->createMock(PriceMatcher::class);

        $this->listener = new MatchingPriceEventListener($this->priceMatcher);
    }

    public function testOnOrderEvent()
    {
        $order = new Order();

        $matchedPrices = ['matched', 'prices'];
        $this->priceMatcher->expects($this->once())
            ->method('getMatchingPrices')
            ->with($order)
            ->willReturn($matchedPrices);

        $event = new OrderEvent($this->createMock(FormInterface::class), $order);
        $this->listener->onOrderEvent($event);

        $actualResult = $event->getData()->getArrayCopy();
        $this->assertArrayHasKey(MatchingPriceEventListener::MATCHED_PRICES_KEY, $actualResult);
        $this->assertEquals([MatchingPriceEventListener::MATCHED_PRICES_KEY => $matchedPrices], $actualResult);
    }
}
