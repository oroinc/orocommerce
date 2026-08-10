<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\EventListener\Order\OrderPreloadingEventListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;

final class OrderPreloadingEventListenerTest extends TestCase
{
    private const array DEFAULT_PRELOADING_CONFIG = [
        'lineItems' => [
            'product' => [
                'kitItems' => [],
                'unitPrecisions' => [
                    'unit' => [],
                ],
            ],
        ],
    ];

    private PreloadingManager&MockObject $preloadingManager;

    private OrderPreloadingEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->preloadingManager = $this->createMock(PreloadingManager::class);

        $this->listener = new OrderPreloadingEventListener($this->preloadingManager);
    }

    public function testOnOrderEventPreloadsOrderWithDefaultConfig(): void
    {
        $order = new Order();
        $event = new OrderEvent($this->createMock(FormInterface::class), $order);

        $this->preloadingManager
            ->expects(self::once())
            ->method('preloadInEntities')
            ->with([$order], self::DEFAULT_PRELOADING_CONFIG);

        $this->listener->onOrderEvent($event);
    }

    public function testOnOrderEventUsesConfigProvidedBySetPreloadingConfig(): void
    {
        $order = new Order();
        $event = new OrderEvent($this->createMock(FormInterface::class), $order);

        $customConfig = ['lineItems' => ['product' => []]];
        $this->listener->setPreloadingConfig($customConfig);

        $this->preloadingManager
            ->expects(self::once())
            ->method('preloadInEntities')
            ->with([$order], $customConfig);

        $this->listener->onOrderEvent($event);
    }
}
