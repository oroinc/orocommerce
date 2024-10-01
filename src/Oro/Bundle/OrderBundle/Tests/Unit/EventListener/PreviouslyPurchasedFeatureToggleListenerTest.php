<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\OrderBundle\EventListener\PreviouslyPurchasedFeatureToggleListener;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;

class PreviouslyPurchasedFeatureToggleListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductReindexManager|\PHPUnit\Framework\MockObject\MockObject */
    private $reindexManager;

    /** @var PreviouslyPurchasedFeatureToggleListener */
    private $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->reindexManager = $this->createMock(ProductReindexManager::class);

        $this->listener = new PreviouslyPurchasedFeatureToggleListener($this->reindexManager);
    }

    public function testConfigOptionNotChanged(): void
    {
        $this->reindexManager->expects(self::never())
            ->method('reindexAllProducts');

        $this->listener->reindexProducts(new ConfigUpdateEvent([], 'global', 0));
    }

    public function testConfigOptionChangedInSystemScope(): void
    {
        $this->reindexManager->expects(self::once())
            ->method('reindexAllProducts')
            ->with(null, true, ['order']);

        $this->listener->reindexProducts(
            new ConfigUpdateEvent(
                ['oro_order.enable_purchase_history' => ['new' => true, 'old' => false]],
                'global',
                0
            )
        );
    }
}
