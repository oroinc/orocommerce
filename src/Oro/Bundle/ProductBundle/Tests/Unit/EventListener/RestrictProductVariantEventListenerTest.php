<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\RestrictProductVariantEvent;
use Oro\Bundle\ProductBundle\EventListener\RestrictProductVariantEventListener;
use Oro\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;

class RestrictProductVariantEventListenerTest extends \PHPUnit_Framework_TestCase
{
    const CONFIG_PATH = 'oro_product.general_frontend_product_visibility';

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    private $configManager;

    /** @var ProductVisibilityQueryBuilderModifier|\PHPUnit_Framework_MockObject_MockObject */
    private $modifier;

    /** @var RestrictProductVariantEventListener */
    private $listener;

    /** @var RestrictProductVariantEvent|\PHPUnit_Framework_MockObject_MockObject*/
    private $event;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)->disableOriginalConstructor()->getMock();

        $this->modifier = $this->getMockBuilder(ProductVisibilityQueryBuilderModifier::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->event = $this->getMockBuilder(RestrictProductVariantEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

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

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
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
