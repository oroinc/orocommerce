<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\EventListener;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\Action\ChannelDisableEvent;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\ShippingBundle\Method\EventListener\ShippingMethodDisableIntegrationListener;
use Oro\Bundle\ShippingBundle\Method\Handler\ShippingMethodDisableHandlerInterface;

class ShippingMethodDisableIntegrationListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var string
     */
    private $channelType;

    /**
     * @var IntegrationIdentifierGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $methodIdentifierGenerator;

    /**
     * @var ShippingMethodDisableHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $handler;

    /**
     * @var ChannelDisableEvent|\PHPUnit\Framework\MockObject\MockObject
     */
    private $event;

    /**
     * @var ShippingMethodDisableIntegrationListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->channelType = 'integration_shipping_method';

        $this->methodIdentifierGenerator = $this->createMock(
            IntegrationIdentifierGeneratorInterface::class
        );
        $this->handler = $this->createMock(
            ShippingMethodDisableHandlerInterface::class
        );
        $this->event = $this->createMock(
            ChannelDisableEvent::class
        );
        $this->listener = new ShippingMethodDisableIntegrationListener(
            $this->channelType,
            $this->methodIdentifierGenerator,
            $this->handler
        );
    }

    public function testOnIntegrationDisable()
    {
        $methodIdentifier = 'method_1';
        $channel = $this->createMock(Channel::class);

        $this->event
            ->expects(static::once())
            ->method('getChannel')
            ->willReturn($channel);

        $channel
            ->expects(static::once())
            ->method('getType')
            ->willReturn($this->channelType);

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

    public function testOnIntegrationDisableWithAnotherType()
    {
        $channel = $this->createMock(Channel::class);

        $this->event
            ->expects(static::once())
            ->method('getChannel')
            ->willReturn($channel);

        $channel
            ->expects(static::once())
            ->method('getType')
            ->willReturn('another_type');

        $this->methodIdentifierGenerator
            ->expects(static::never())
            ->method('generateIdentifier');

        $this->handler
            ->expects(static::never())
            ->method('handleMethodDisable');

        $this->listener->onIntegrationDisable($this->event);
    }
}
