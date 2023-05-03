<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\RestrictProductVariantEvent;
use Oro\Bundle\ProductBundle\EventListener\RestrictProductVariantEventListener;
use Oro\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;

class RestrictProductVariantEventListenerTest extends \PHPUnit\Framework\TestCase
{
    private const CONFIG_PATH = 'oro_product.general_frontend_product_visibility';

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ProductVisibilityQueryBuilderModifier|\PHPUnit\Framework\MockObject\MockObject */
    private $modifier;

    /** @var RestrictProductVariantEvent|\PHPUnit\Framework\MockObject\MockObject */
    private $event;

    /** @var RestrictProductVariantEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->modifier = $this->createMock(ProductVisibilityQueryBuilderModifier::class);
        $this->event = $this->createMock(RestrictProductVariantEvent::class);

        $this->listener = new RestrictProductVariantEventListener(
            $this->configManager,
            $this->modifier,
            self::CONFIG_PATH
        );
    }

    public function testOnRestrictProductVariantEvent()
    {
        $inventoryStatuses = [
            'status',
            'status 2'
        ];

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->event->expects($this->exactly(2))
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $this->modifier->expects($this->once())
            ->method('modifyByStatus')
            ->with($queryBuilder, [Product::STATUS_ENABLED]);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(self::CONFIG_PATH)
            ->willReturn($inventoryStatuses);

        $this->modifier->expects($this->once())
            ->method('modifyByInventoryStatus')
            ->with($queryBuilder, $inventoryStatuses);

        $this->listener->onRestrictProductVariantEvent($this->event);
    }
}
