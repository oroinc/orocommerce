<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\EventListener\PreloadOrderOnBeforeFormSubmitListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;

final class PreloadOrderOnBeforeFormSubmitListenerTest extends TestCase
{
    private const array DEFAULT_PRELOADING_CONFIG = [
        'lineItems' => [
            'product' => [
                'unitPrecisions' => [],
            ],
        ],
    ];

    private PreloadingManager&MockObject $preloadingManager;

    private PreloadOrderOnBeforeFormSubmitListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->preloadingManager = $this->createMock(PreloadingManager::class);

        $this->listener = new PreloadOrderOnBeforeFormSubmitListener($this->preloadingManager);
    }

    public function testOnBeforeFormSubmitPreloadsOrderWithDefaultConfig(): void
    {
        $order = new Order();
        $order->addLineItem(new OrderLineItem());

        $this->preloadingManager
            ->expects(self::once())
            ->method('preloadInEntities')
            ->with([$order], self::DEFAULT_PRELOADING_CONFIG);

        $this->listener->onBeforeFormSubmit(
            new FormProcessEvent($this->createMock(FormInterface::class), $order)
        );
    }

    public function testOnBeforeFormSubmitUsesConfigProvidedBySetPreloadingConfig(): void
    {
        $order = new Order();
        $customConfig = ['lineItems' => ['product' => ['unitPrecisions' => ['unit' => []]]]];

        $this->listener->setPreloadingConfig($customConfig);

        $this->preloadingManager
            ->expects(self::once())
            ->method('preloadInEntities')
            ->with([$order], $customConfig);

        $this->listener->onBeforeFormSubmit(
            new FormProcessEvent($this->createMock(FormInterface::class), $order)
        );
    }

    public function testOnBeforeFormSubmitDoesNothingWhenDataIsNotOrder(): void
    {
        $this->preloadingManager
            ->expects(self::never())
            ->method('preloadInEntities');

        $this->listener->onBeforeFormSubmit(
            new FormProcessEvent($this->createMock(FormInterface::class), new OrderLineItem())
        );
    }
}
