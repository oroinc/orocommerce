<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\EventListener;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\Action\ChannelDeleteEvent;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\ShippingBundle\Method\Event\MethodRemovalEventDispatcherInterface;
use Oro\Bundle\ShippingBundle\Method\EventListener\IntegrationRemovalListener;

class IntegrationRemovalListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var string
     */
    private $channelType;

    /**
     * @var IntegrationIdentifierGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $identifierGenerator;

    /**
     * @var MethodRemovalEventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dispatcher;

    /**
     * @var IntegrationRemovalListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->channelType = 'shipping_method';
        $this->identifierGenerator = $this->createMock(IntegrationIdentifierGeneratorInterface::class);
        $this->dispatcher = $this->createMock(MethodRemovalEventDispatcherInterface::class);

        $this->listener = new IntegrationRemovalListener(
            $this->channelType,
            $this->identifierGenerator,
            $this->dispatcher
        );
    }

    public function testPreRemove()
    {
        /** @var Channel|\PHPUnit\Framework\MockObject\MockObject $channel */
        $channel = $this->createMock(Channel::class);
        $channel->expects(static::once())
            ->method('getType')
            ->willReturn($this->channelType);

        /** @var ChannelDeleteEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(ChannelDeleteEvent::class);
        $event->expects(static::any())
            ->method('getChannel')
            ->willReturn($channel);

        $identifier = 'method';

        $this->identifierGenerator->expects(static::once())
            ->method('generateIdentifier')
            ->with($channel)
            ->willReturn($identifier);

        $this->dispatcher->expects(static::once())
            ->method('dispatch')
            ->with($identifier);

        $this->listener->onRemove($event);
    }

    public function testPreRemoveOtherType()
    {
        /** @var Channel|\PHPUnit\Framework\MockObject\MockObject $channel */
        $channel = $this->createMock(Channel::class);
        $channel->expects(static::once())
            ->method('getType')
            ->willReturn('other_type');

        /** @var ChannelDeleteEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(ChannelDeleteEvent::class);
        $event->expects(static::any())
            ->method('getChannel')
            ->willReturn($channel);

        $this->identifierGenerator->expects(static::never())
            ->method('generateIdentifier');

        $this->dispatcher->expects(static::never())
            ->method('dispatch');

        $this->listener->onRemove($event);
    }
}
