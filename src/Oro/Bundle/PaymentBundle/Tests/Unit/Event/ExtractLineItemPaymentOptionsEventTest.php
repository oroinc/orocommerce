<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Event;

use Oro\Bundle\PaymentBundle\Event\ExtractLineItemPaymentOptionsEvent;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;

class ExtractLineItemPaymentOptionsEventTest extends AbstractExtractOptionsEventTestCase
{
    /** @var ExtractLineItemPaymentOptionsEvent */
    protected $event;

    /** @var LineItemsAwareInterface|\PHPUnit_Framework_MockObject_MockObject $entityMock */
    protected $entityMock;

    protected function setUp()
    {
        $this->entityMock = $this->getMockBuilder(LineItemsAwareInterface::class)->getMock();
        $this->event = new ExtractLineItemPaymentOptionsEvent($this->entityMock, $this->keys);
    }

    public function testGetEntity()
    {
        $this->assertSame($this->entityMock, $this->event->getEntity());
    }
}
