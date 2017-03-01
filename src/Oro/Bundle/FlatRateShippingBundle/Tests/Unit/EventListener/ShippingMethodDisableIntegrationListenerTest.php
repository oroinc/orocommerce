<?php

namespace Oro\Bundle\FlatRateShippingBundle\Tests\Unit\EventListener;

use Oro\Bundle\IntegrationBundle\Event\Action\ChannelDisableEvent;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\FlatRateShippingBundle\EventListener\ShippingMethodDisableIntegrationListener;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;
use Oro\Bundle\ShippingBundle\Method\Handler\ShippingMethodDisableHandlerInterface;

class ShippingMethodDisableIntegrationListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var IntegrationMethodIdentifierGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $methodIdentifierGenerator;

    /**
     * @var ShippingMethodDisableHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $handler;

    /**
     * @var ChannelDisableEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event;

    /**
     * @var ShippingMethodDisableIntegrationListener
     */
    private $listener;

    protected function setUp()
    {
        $this->methodIdentifierGenerator = $this->createMock(
            IntegrationMethodIdentifierGeneratorInterface::class
        );
        $this->handler = $this->createMock(
            ShippingMethodDisableHandlerInterface::class
        );
        $this->event = $this->createMock(
            ChannelDisableEvent::class
        );
        $this->listener = new ShippingMethodDisableIntegrationListener(
            $this->methodIdentifierGenerator,
            $this->handler
        );
    }

    public function testOnIntegrationDisable()
    {
        $methodIdentifier = 'method_1';
        $channel = $this->createMock(Channel::class);
        $type = 'flat_rate';

        $this->event
            ->expects(static::once())
            ->method('getChannel')
            ->willReturn($channel);

        $channel
            ->expects(static::once())
            ->method('getType')
            ->willReturn($type);

        $this->methodIdentifierGenerator
            ->expects(static::once())
            ->method('generateIdentifier')
            ->with($channel)
            ->willReturn($methodIdentifier);

        $this->handler
            ->expects(static::once())
            ->method('handleMethodDisable')
            ->with($methodIdentifier);

        $this->listener->onIntegrationDisable($this->event);
    }
}
