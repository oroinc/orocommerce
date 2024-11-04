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
    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $producer;

    /** @var MessageFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $messageFactory;

    /** @var ConfigurationListener */
    private $listener;

    #[\Override]
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
        $event = new ConfigUpdateEvent([], 'global', 0);

        $this->producer->expects(self::never())
            ->method(self::anything());

        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfterNoSignificantConfigChanges(): void
    {
        $event = new ConfigUpdateEvent(
            [
                'oro_product.general_frontend_product_visibility' => [
                    'old' => ['in_stock', 'out_of_stock'],
                    'new' => ['in_stock', 'out_of_stock', 'discontinued']
                ]
            ],
            'global',
            0
        );

        $this->producer->expects(self::never())
            ->method(self::anything());

        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfter(): void
    {
        $event = new ConfigUpdateEvent(
            [
                'oro_product.general_frontend_product_visibility' => [
                    'old' => ['in_stock', 'out_of_stock', 'discontinued'],
                    'new' => ['in_stock']
                ]
            ],
            'website',
            1
        );

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
            ->with(InvalidateTotalsByInventoryStatusPerWebsiteTopic::getName(), $data);

        $this->listener->onUpdateAfter($event);
    }
}
