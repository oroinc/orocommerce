<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ShoppingListBundle\Async\MessageFactory;
use Oro\Bundle\ShoppingListBundle\Async\Topic\InvalidateTotalsByInventoryStatusPerWebsiteTopic;
use Oro\Bundle\ShoppingListBundle\EventListener\ConfigurationListener;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class ConfigurationListenerTest extends \PHPUnit\Framework\TestCase
{
    private const CONFIG_PATH = 'oro_product.general_frontend_product_visibility';

    private MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject $producer;

    private MessageFactory|\PHPUnit\Framework\MockObject\MockObject $messageFactory;

    private ConfigurationListener $listener;

    protected function setUp(): void
    {
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->messageFactory = $this->createMock(MessageFactory::class);

        $this->listener = new ConfigurationListener(
            $this->producer,
            $this->messageFactory
        );
    }

    public function testOnUpdateAfterNotChangedConfig(): void
    {
        /** @var ConfigUpdateEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(ConfigUpdateEvent::class);
        $event->expects(self::once())
            ->method('isChanged')
            ->with(self::CONFIG_PATH)
            ->willReturn(false);

        $this->producer->expects(self::never())
            ->method(self::anything());

        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfterNoSignificantConfigChanges(): void
    {
        /** @var ConfigUpdateEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(ConfigUpdateEvent::class);
        $event->expects(self::once())
            ->method('isChanged')
            ->with(self::CONFIG_PATH)
            ->willReturn(true);
        $event->expects(self::once())
            ->method('getOldValue')
            ->with(self::CONFIG_PATH)
            ->willReturn(['in_stock', 'out_of_stock']);
        $event->expects(self::once())
            ->method('getNewValue')
            ->with(self::CONFIG_PATH)
            ->willReturn(['in_stock', 'out_of_stock', 'discontinued']);

        $this->producer->expects(self::never())
            ->method(self::anything());

        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfter(): void
    {
        /** @var ConfigUpdateEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(ConfigUpdateEvent::class);
        $event->expects(self::once())
            ->method('isChanged')
            ->with(self::CONFIG_PATH)
            ->willReturn(true);
        $event->expects(self::once())
            ->method('getOldValue')
            ->with(self::CONFIG_PATH)
            ->willReturn(['in_stock', 'out_of_stock', 'discontinued']);
        $event->expects(self::once())
            ->method('getNewValue')
            ->with(self::CONFIG_PATH)
            ->willReturn(['in_stock']);
        $event->expects(self::once())
            ->method('getScope')
            ->willReturn('website');
        $event->expects(self::once())
            ->method('getScopeId')
            ->willReturn(1);

        $data = [
            'context' => [
                'class' => Website::class,
                'id' => 1
            ]
        ];
        $this->messageFactory->expects(self::once())
            ->method('createShoppingListTotalsInvalidateMessageForConfigScope')
            ->with('website', 1)
            ->willReturn($data);
        $this->producer->expects(self::once())
            ->method('send')
            ->with(
                InvalidateTotalsByInventoryStatusPerWebsiteTopic::getName(),
                $data
            );

        $this->listener->onUpdateAfter($event);
    }
}
