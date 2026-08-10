<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\DraftSession;

use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\EventListener\DraftSession\PreloadOrderRelationsOnEntityFromDraftSyncBeforeEventListener;
use Oro\Component\DraftSession\Event\EntityFromDraftSyncBeforeEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PreloadOrderRelationsOnEntityFromDraftSyncBeforeEventListenerTest extends TestCase
{
    private const array DEFAULT_PRELOADING_CONFIG = [
        'lineItems' => [
            'product' => [],
            'parentProduct' => [],
            'productUnit' => [],
            'draftSource' => [
                'kitItemLineItems' => [],
            ],
            'kitItemLineItems' => [
                'kitItem' => [],
                'product' => [],
                'productUnit' => [],
            ],
        ],
        'discounts' => [],
        'billingAddress' => [
            'country' => [],
            'region' => [],
            'customerAddress' => [],
            'customerUserAddress' => [],
        ],
        'shippingAddress' => [
            'country' => [],
            'region' => [],
            'customerAddress' => [],
            'customerUserAddress' => [],
        ],
        'owner' => [],
        'organization' => [],
        'customer' => [],
        'customerUser' => [],
        'website' => [],
    ];

    private PreloadingManager&MockObject $preloadingManager;

    private PreloadOrderRelationsOnEntityFromDraftSyncBeforeEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->preloadingManager = $this->createMock(PreloadingManager::class);

        $this->listener = new PreloadOrderRelationsOnEntityFromDraftSyncBeforeEventListener(
            $this->preloadingManager
        );
    }

    public function testOnEntityFromDraftSyncBeforePreloadsOrderRelationsWhenSourceIsOrder(): void
    {
        $source = new Order();
        $target = new Order();
        $event = new EntityFromDraftSyncBeforeEvent($source, $target);

        $this->preloadingManager
            ->expects(self::once())
            ->method('preloadInEntities')
            ->with([$source, $target], self::DEFAULT_PRELOADING_CONFIG);

        $this->listener->onEntityFromDraftSyncBefore($event);
    }

    public function testOnEntityFromDraftSyncBeforeDoesNothingWhenSourceIsNotOrder(): void
    {
        $event = new EntityFromDraftSyncBeforeEvent(new OrderLineItem(), new OrderLineItem());

        $this->preloadingManager
            ->expects(self::never())
            ->method('preloadInEntities');

        $this->listener->onEntityFromDraftSyncBefore($event);
    }

    public function testOnEntityFromDraftSyncBeforeUsesConfigProvidedBySetPreloadingConfig(): void
    {
        $source = new Order();
        $target = new Order();
        $event = new EntityFromDraftSyncBeforeEvent($source, $target);

        $customConfig = ['lineItems' => ['product' => []]];
        $this->listener->setPreloadingConfig($customConfig);

        $this->preloadingManager
            ->expects(self::once())
            ->method('preloadInEntities')
            ->with([$source, $target], $customConfig);

        $this->listener->onEntityFromDraftSyncBefore($event);
    }
}
