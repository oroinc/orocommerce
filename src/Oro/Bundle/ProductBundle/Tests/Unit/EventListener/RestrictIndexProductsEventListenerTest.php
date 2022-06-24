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
    const CONFIG_PATH = 'config.path';

    /** @var \PHPUnit\Framework\MockObject\MockObject|ProductVisibilityQueryBuilderModifier */
    protected $modifier;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    protected $configManager;

    /** @var RestrictIndexProductsEventListener */
    protected $listener;

    /** @var \PHPUnit\Framework\MockObject\MockObject|QueryBuilder */
    protected $queryBuilder;

    /** @var \PHPUnit\Framework\MockObject\MockObject|WebsiteContextManager */
    protected $websiteContext;

    protected function setUp(): void
    {
        $this->modifier = $this->getMockBuilder(ProductVisibilityQueryBuilderModifier::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder(ConfigManager::class)->disableOriginalConstructor()->getMock();
        $this->queryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $this->websiteContext = $this->getMockBuilder(WebsiteContextManager::class)
            ->disableOriginalConstructor()->getMock();

        $this->listener = new RestrictIndexProductsEventListener(
            $this->configManager,
            $this->modifier,
            self::CONFIG_PATH
        );
        $this->listener->setWebsiteContextManager($this->websiteContext);
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
