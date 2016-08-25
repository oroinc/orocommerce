<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntitiesEvent;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\EventListener\RestrictIndexProductsEventListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;

class RestrictIndexProductsEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ProductVisibilityQueryBuilderModifier */
    protected $modifierMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager */
    protected $configManagerMock;

    /** @var RestrictIndexProductsEventListener */
    protected $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject|QueryBuilder */
    protected $queryBuilderMock;

    protected function setUp()
    {
        $this->modifierMock = $this->getMockBuilder(ProductVisibilityQueryBuilderModifier::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManagerMock = $this->getMockBuilder(ConfigManager::class)->disableOriginalConstructor()->getMock();

        $this->queryBuilderMock = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $this->listener = new RestrictIndexProductsEventListener($this->configManagerMock, $this->modifierMock);
    }

    public function testOnRestrictIndexEntitiesEvent()
    {
        $path = 'some_config_path';
        $event = new RestrictIndexEntitiesEvent($this->queryBuilderMock, Product::class, []);
        $this->modifierMock->expects($this->once())->method('modifyByStatus')->with(
            $this->queryBuilderMock,
            [Product::STATUS_ENABLED]
        );
        $inventoryStatuses = ['status' => 1];
        $this->configManagerMock->expects($this->once())->method('get')->with($path)->willReturn($inventoryStatuses);
        $this->modifierMock->expects($this->once())->method('modifyByInventoryStatus')
            ->with($this->queryBuilderMock, $inventoryStatuses);
        $this->listener->setSystemConfigurationPath($path);
        $this->listener->onRestrictIndexEntitiesEvent($event);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage SystemConfigurationPath not configured for RestrictIndexProductsEventListener
     */
    public function testConfigNotPassedExeption()
    {
        $event = new RestrictIndexEntitiesEvent($this->queryBuilderMock, Product::class, []);
        $this->listener->onRestrictIndexEntitiesEvent($event);
    }

    public function testOtherEntityPassed()
    {
        $event = new RestrictIndexEntitiesEvent($this->queryBuilderMock, \stdClass::class, []);
        $this->listener->onRestrictIndexEntitiesEvent($event);
        $this->modifierMock->expects($this->never())->method('modifyByInventoryStatus');
        $this->modifierMock->expects($this->never())->method('modifyByStatus');
    }
}
