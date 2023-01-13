<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigChangeSet;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\OrderBundle\EventListener\PreviouslyPurchasedFeatureToggleListener;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;

class PreviouslyPurchasedFeatureToggleListenerTest extends \PHPUnit\Framework\TestCase
{
    private const CONFIG_KEY = 'oro_order.enable_purchase_history';

    /** @var ProductReindexManager|\PHPUnit\Framework\MockObject\MockObject */
    private $reindexManager;

    /** @var PreviouslyPurchasedFeatureToggleListener */
    private $listener;

    protected function setUp(): void
    {
        $this->reindexManager = $this->createMock(ProductReindexManager::class);

        $this->listener = new PreviouslyPurchasedFeatureToggleListener($this->reindexManager);
    }

    private function getConfigUpdateEvent(array $changeSet = [], ?string $scope = null): ConfigUpdateEvent
    {
        return new ConfigUpdateEvent(new ConfigChangeSet($changeSet), $scope);
    }

    public function testConfigOptionNotChanged(): void
    {
        $this->reindexManager->expects($this->never())
            ->method('reindexAllProducts');

        $event = $this->getConfigUpdateEvent();
        $this->listener->reindexProducts($event);
    }

    public function testConfigOptionChangedInSystemScope(): void
    {
        $event = $this->getConfigUpdateEvent(
            [self::CONFIG_KEY => ['new' => true, 'old' => false]],
            'system'
        );

        $this->reindexManager->expects($this->once())
            ->method('reindexAllProducts')
            ->with(null, true, ['order']);

        $this->listener->reindexProducts($event);
    }
}
