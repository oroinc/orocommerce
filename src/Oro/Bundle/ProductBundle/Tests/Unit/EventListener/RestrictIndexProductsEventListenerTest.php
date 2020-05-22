<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\RestrictIndexProductsEventListener;
use Oro\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;

class RestrictIndexProductsEventListenerTest extends \PHPUnit\Framework\TestCase
{
    const CONFIG_PATH = 'config.path';

    /** @var \PHPUnit\Framework\MockObject\MockObject|ProductVisibilityQueryBuilderModifier */
    protected $modifier;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    protected $configManager;

    /** @var RestrictIndexProductsEventListener */
    protected $listener;

    /** @var \PHPUnit\Framework\MockObject\MockObject|QueryBuilder */
    protected $queryBuilder;

    protected function setUp(): void
    {
        $this->modifier = $this->getMockBuilder(ProductVisibilityQueryBuilderModifier::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder(ConfigManager::class)->disableOriginalConstructor()->getMock();
        $this->queryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();

        $this->listener = new RestrictIndexProductsEventListener(
            $this->configManager,
            $this->modifier,
            self::CONFIG_PATH
        );
    }

    public function testOnRestrictIndexEntitiesEvent()
    {
        $this->modifier->expects($this->once())
            ->method('modifyByStatus')
            ->with($this->queryBuilder, [Product::STATUS_ENABLED]);

        $inventoryStatuses = ['status' => 1];
        $this->configManager->expects($this->once())
            ->method('get')
            ->with(self::CONFIG_PATH)
            ->willReturn($inventoryStatuses);

        $this->modifier->expects($this->once())
            ->method('modifyByInventoryStatus')
            ->with($this->queryBuilder, $inventoryStatuses);

        $event = new RestrictIndexEntityEvent($this->queryBuilder, []);
        $this->listener->onRestrictIndexEntityEvent($event);
    }
}
