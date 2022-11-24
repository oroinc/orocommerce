<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\RestrictIndexProductsEventListener;
use Oro\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;

class RestrictIndexProductsEventListenerTest extends \PHPUnit\Framework\TestCase
{
    private const CONFIG_PATH = 'config.path';

    /** @var \PHPUnit\Framework\MockObject\MockObject|ProductVisibilityQueryBuilderModifier */
    private $modifier;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    private $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|QueryBuilder */
    private $queryBuilder;

    /** @var \PHPUnit\Framework\MockObject\MockObject|WebsiteContextManager */
    private $websiteContext;

    /** @var RestrictIndexProductsEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->modifier = $this->createMock(ProductVisibilityQueryBuilderModifier::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->websiteContext = $this->createMock(WebsiteContextManager::class);

        $this->listener = new RestrictIndexProductsEventListener(
            $this->configManager,
            $this->modifier,
            $this->websiteContext,
            self::CONFIG_PATH
        );
    }

    public function testOnRestrictIndexEntitiesEvent()
    {
        $context = [
            'currentWebsiteId' => 1,
        ];

        $this->modifier->expects($this->once())
            ->method('modifyByStatus')
            ->with($this->queryBuilder, [Product::STATUS_ENABLED]);

        $inventoryStatuses = ['status' => 1];
        $this->configManager->expects($this->once())
            ->method('get')
            ->with(self::CONFIG_PATH)
            ->willReturn($inventoryStatuses);

        $this->websiteContext->expects($this->once())
            ->method('getWebsite')
            ->with($context);

        $this->modifier->expects($this->once())
            ->method('modifyByInventoryStatus')
            ->with($this->queryBuilder, $inventoryStatuses);

        $event = new RestrictIndexEntityEvent($this->queryBuilder, $context);
        $this->listener->onRestrictIndexEntityEvent($event);
    }
}
