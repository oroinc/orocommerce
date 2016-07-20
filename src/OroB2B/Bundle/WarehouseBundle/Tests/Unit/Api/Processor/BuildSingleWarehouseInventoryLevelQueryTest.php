<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorOrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Util\CriteriaConnector;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

use OroB2B\Bundle\ProductBundle\Api\Processor\BuildSingleProductQuery;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WarehouseBundle\Api\Processor\BuildSingleWarehouseInventoryLevelQuery;
use OroB2B\Bundle\WarehouseBundle\Entity\Helper\WarehouseCounter;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class BuildSingleWarehouseInventoryLevelQueryTest extends GetProcessorOrmRelatedTestCase
{
    /** @var UpdateContext */
    protected $context;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $criteriaConnector;

    /** @var WarehouseCounter|\PHPUnit_Framework_MockObject_MockObject */
    protected $warehouseCounter;

    /** @var BuildSingleWarehouseInventoryLevelQuery */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->context = new UpdateContext($this->configProvider, $this->metadataProvider);
        $this->context->setVersion(self::TEST_VERSION);
        $this->context->getRequestType()->add(self::TEST_REQUEST_TYPE);
        $this->context->setConfigExtras(
            [
                new EntityDefinitionConfigExtra($this->context->getAction()),
                new FiltersConfigExtra()
            ]
        );

        $this->criteriaConnector = $this->getMockBuilder(CriteriaConnector::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->warehouseCounter = $this->getMockBuilder(WarehouseCounter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new BuildSingleWarehouseInventoryLevelQuery(
            $this->doctrineHelper,
            $this->criteriaConnector,
            $this->warehouseCounter
        );
    }


    public function testProcessWhenCriteriaObjectDoesNotExist()
    {
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasQuery());
    }

    public function testProcessForNotManageableEntity()
    {
        $className = 'Test\Class';

        $this->notManageableClassNames = [$className];

        $this->context->setClassName($className);
        $this->processor->process($this->context);

        $this->assertNull($this->context->getQuery());
    }

    public function testProductNotExistsInRequest()
    {
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasQuery());
    }

    public function testMoreWarehousesAndNoProduct()
    {
        $this->warehouseCounter->expects($this->once())
            ->method('areMoreWarehouses')
            ->willReturn(true);

        $this->context->setRequestData(['sku' => 'product.1']);

        $resolver = $this->getMockBuilder(EntityClassResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $criteria = new Criteria($resolver);

        $this->context->setCriteria($criteria);

        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasQuery());
    }

    public function testProcessBuildQueryWithMultipleWarehouses()
    {
        $this->warehouseCounter->expects($this->once())
            ->method('areMoreWarehouses')
            ->willReturn(true);

        $this->context->setRequestData(
            [
                'sku' => 'product.1',
                'warehouse' => 1,
                'unit' => 'liter'
            ]
        );

        $resolver = $this->getMockBuilder(EntityClassResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $criteria = new Criteria($resolver);

        $this->criteriaConnector->expects($this->once())
            ->method('applyCriteria');

        $this->context->setCriteria($criteria);
        $this->context->setClassName(WarehouseInventoryLevel::class);

        $this->processor->process($this->context);

        $this->assertTrue($this->context->hasQuery());

        $query = 'SELECT e FROM %s e LEFT JOIN e.product product LEFT JOIN e.productUnitPrecision productPrecision 
LEFT JOIN productPrecision.unit unit WHERE product.sku = :sku AND unit.code = :unit 
AND e.warehouse = :warehouse';
        $query = str_replace("\n", '', $query);

        $this->assertEquals(
            $this->context->getQuery()->getDql(),
            sprintf(
                $query,
                WarehouseInventoryLevel::class
            )
        );
    }

    public function testProcessBuildQueryWithOneWarehouses()
    {
        $this->warehouseCounter->expects($this->once())
            ->method('areMoreWarehouses')
            ->willReturn(false);

        $this->context->setRequestData(
            [
                'sku' => 'product.1',
                'unit' => 'liter'
            ]
        );

        $resolver = $this->getMockBuilder(EntityClassResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $criteria = new Criteria($resolver);

        $this->criteriaConnector->expects($this->once())
            ->method('applyCriteria');

        $this->context->setCriteria($criteria);
        $this->context->setClassName(WarehouseInventoryLevel::class);

        $this->processor->process($this->context);

        $this->assertTrue($this->context->hasQuery());

        $query = 'SELECT e FROM %s e LEFT JOIN e.product product LEFT JOIN e.productUnitPrecision productPrecision 
LEFT JOIN productPrecision.unit unit WHERE product.sku = :sku AND unit.code = :unit';
        $query = str_replace("\n", '', $query);

        $this->assertEquals(
            $this->context->getQuery()->getDql(),
            sprintf(
                $query,
                WarehouseInventoryLevel::class
            )
        );
    }
}
