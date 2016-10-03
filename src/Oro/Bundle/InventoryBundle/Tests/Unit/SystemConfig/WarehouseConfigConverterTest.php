<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\SystemConfig;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Entity\Warehouse;
use Oro\Bundle\WarehouseProBundle\SystemConfig\WarehouseConfig;
use Oro\Bundle\WarehouseProBundle\SystemConfig\WarehouseConfigConverter;
use Oro\Component\Testing\Unit\EntityTrait;

class WarehouseConfigConverterTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $warehouseClass;

    /**
     * @var WarehouseConfigConverter
     */
    protected $warehouseConfigConverter;

    protected function setUp()
    {
        $this->doctrineHelper = $this
            ->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->warehouseClass = Warehouse::class;
        $this->warehouseConfigConverter = new WarehouseConfigConverter($this->doctrineHelper, $this->warehouseClass);
    }

    public function testConvertBeforeSave()
    {
        /** @var Warehouse $warehouse1 */
        $warehouse1 = $this->getEntity(Warehouse::class, ['id' => 1]);
        /** @var Warehouse $warehouse2 */
        $warehouse2 = $this->getEntity(Warehouse::class, ['id' => 2]);
        $testData = [
            new WarehouseConfig($warehouse1, 100),
            new WarehouseConfig($warehouse2, 200)
        ];
        $expected = [
            ['warehouse' => 1, 'priority' => 100],
            ['warehouse' => 2, 'priority' => 200],
        ];
        $actual = $this->warehouseConfigConverter->convertBeforeSave($testData);
        $this->assertEquals($expected, $actual);
    }

    public function testConvertFromSaved()
    {
        /** @var Warehouse $warehouse1 */
        $warehouse1 = $this->getEntity(Warehouse::class, ['id' => 1]);
        /** @var Warehouse $warehouse2 */
        $warehouse2 = $this->getEntity(Warehouse::class, ['id' => 2]);
        $expected = [
            new WarehouseConfig($warehouse1, 100),
            new WarehouseConfig($warehouse2, 200),
        ];
        $testData = [
            ['warehouse' => 1, 'priority' => 100],
            ['warehouse' => 2, 'priority' => 200],
        ];

        $this->mockFindById([1, 2], [$warehouse1, $warehouse2]);

        $actual = $this->warehouseConfigConverter->convertFromSaved($testData);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Warehouse record with id 5 not found, while reading enabled warehouses system
     */
    public function testConvertFromSavedInvalidData()
    {
        /** @var Warehouse $warehouse1 */
        $warehouse1 = $this->getEntity(Warehouse::class, ['id' => 1]);
        /** @var Warehouse $warehouse2 */
        $warehouse2 = $this->getEntity(Warehouse::class, ['id' => 2]);

        $configs = [
            ['warehouse' => 1, 'priority' => 100],
            ['warehouse' => 5, 'priority' => 500],
        ];

        $this->mockFindById([1, 5], [$warehouse1, $warehouse2]);

        $this->warehouseConfigConverter->convertFromSaved($configs);
    }

    /**
     * @param array $ids
     * @param array $warehouses
     */
    protected function mockFindById(array $ids, array $warehouses)
    {
        $repository = $this
            ->getMockBuilder('\Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this
            ->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with($this->warehouseClass)
            ->willReturn($repository);

        $repository->expects($this->once())
            ->method('findBy')
            ->with(['id' => $ids])
            ->willReturn($warehouses);
    }
}
