<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Symfony\Component\Form\FormInterface;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\EventListener\Order\MatchingPriceEventListener;
use Oro\Bundle\OrderBundle\Pricing\PriceMatcher;

class MatchingPriceEventListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var MatchingPriceEventListener */
    protected $listener;

    /** @var PriceMatcher|\PHPUnit_Framework_MockObject_MockObject */
    protected $priceMatcher;

    /** @var FormInterface */
    protected $form;

    protected function setUp()
    {
        $this->form = $this->getMock('Symfony\Component\Form\FormInterface');

        $this->priceMatcher = $this->getMockBuilder('Oro\Bundle\OrderBundle\Pricing\PriceMatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new MatchingPriceEventListener($this->priceMatcher);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->priceMatcher, $this->form);
    }

    public function testOnOrderEvent()
    {
        $order = new Order();

        $matchedPrices = ['matched', 'prices'];
        $this->priceMatcher
            ->expects($this->once())
            ->method('getMatchingPrices')
            ->with($order)
            ->willReturn($matchedPrices);

        $event = new OrderEvent($this->form, $order);
        $this->listener->onOrderEvent($event);

        $actualResult = $event->getData()->getArrayCopy();
        $this->assertArrayHasKey(MatchingPriceEventListener::MATCHED_PRICES_KEY, $actualResult);
        $this->assertEquals([MatchingPriceEventListener::MATCHED_PRICES_KEY => $matchedPrices], $actualResult);
    }
}
