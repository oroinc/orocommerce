<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ShoppingListBundle\Async\MessageFactory;
use Oro\Bundle\ShoppingListBundle\Async\Topics;
use Oro\Bundle\ShoppingListBundle\EventListener\ConfigurationListener;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class ConfigurationListenerTest extends \PHPUnit\Framework\TestCase
{
    private const CONFIG_PATH = 'oro_product.general_frontend_product_visibility';

    /**
     * @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $producer;

    /**
     * @var MessageFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $messageFactory;

    /**
     * @var ConfigurationListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->messageFactory = $this->createMock(MessageFactory::class);

        $this->listener = new ConfigurationListener(
            $this->producer,
            $this->messageFactory
        );
    }

    public function testOnUpdateAfterNotChangedConfig()
    {
        /** @var ConfigUpdateEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(ConfigUpdateEvent::class);
        $event->expects($this->once())
            ->method('isChanged')
            ->with(self::CONFIG_PATH)
            ->willReturn(false);

        $this->producer->expects($this->never())
            ->method($this->anything());

        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfterNoSignificantConfigChanges()
    {
        /** @var ConfigUpdateEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(ConfigUpdateEvent::class);
        $event->expects($this->once())
            ->method('isChanged')
            ->with(self::CONFIG_PATH)
            ->willReturn(true);
        $event->expects($this->once())
            ->method('getOldValue')
            ->with(self::CONFIG_PATH)
            ->willReturn(['in_stock', 'out_of_stock']);
        $event->expects($this->once())
            ->method('getNewValue')
            ->with(self::CONFIG_PATH)
            ->willReturn(['in_stock', 'out_of_stock', 'discontinued']);

        $this->producer->expects($this->never())
            ->method($this->anything());

        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfter()
    {
        /** @var ConfigUpdateEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(ConfigUpdateEvent::class);
        $event->expects($this->once())
            ->method('isChanged')
            ->with(self::CONFIG_PATH)
            ->willReturn(true);
        $event->expects($this->once())
            ->method('getOldValue')
            ->with(self::CONFIG_PATH)
            ->willReturn(['in_stock', 'out_of_stock', 'discontinued']);
        $event->expects($this->once())
            ->method('getNewValue')
            ->with(self::CONFIG_PATH)
            ->willReturn(['in_stock']);
        $event->expects($this->once())
            ->method('getScope')
            ->willReturn('website');
        $event->expects($this->once())
            ->method('getScopeId')
            ->willReturn(1);

        $data = [
            'context' => [
                'class' => Website::class,
                'id' => 1
            ]
        ];
        $this->messageFactory->expects($this->once())
            ->method('createShoppingListTotalsInvalidateMessageForConfigScope')
            ->with('website', 1)
            ->willReturn($data);
        $this->producer->expects($this->once())
            ->method('send')
            ->with(
                Topics::INVALIDATE_TOTALS_BY_INVENTORY_STATUS_PER_WEBSITE,
                $data
            );

        $this->listener->onUpdateAfter($event);
    }
}
