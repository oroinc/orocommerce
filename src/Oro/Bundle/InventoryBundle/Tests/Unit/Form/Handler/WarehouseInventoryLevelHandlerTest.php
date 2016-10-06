<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Component\Testing\Unit\EntityTrait;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\InventoryBundle\Form\Handler\InventoryLevelHandler;
use Oro\Bundle\WarehouseBundle\Entity\Warehouse;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Product;


class WarehouseInventoryLevelHandlerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FormInterface
     */
    protected $form;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected $manager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RoundingServiceInterface
     */
    protected $roundingService;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var InventoryLevelHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->form = $this->getMock('Symfony\Component\Form\FormInterface');
        $this->manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->roundingService = $this->getMock('Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface');
        $this->request = new Request();

        $this->handler = new InventoryLevelHandler(
            $this->form,
            $this->manager,
            $this->request,
            $this->roundingService
        );
    }

    public function testProcessGet()
    {
        $this->form->expects($this->never())
            ->method('submit');

        $this->handler->process();
    }

    public function testProcessInvalidForm()
    {
        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);
        $this->form->expects($this->never())
            ->method('getData');

        $this->handler->process();
    }

    /**
     * @param mixed $formData
     * @param InventoryLevel[] $existingLevels
     * @param array $expectedLevels
     * @dataProvider processDataProvider
     */
    public function testProcess($formData, array $existingLevels, array $expectedLevels)
    {
        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $this->form->expects($this->once())
            ->method('getData')
            ->willReturn($formData);

        // mock repository behvaiour
        $repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $repository->expects($this->any())
            ->method('findOneBy')
            ->willReturnCallback(
                function (array $criteria) use ($existingLevels) {
                    /** @var Warehouse $warehouse */
                    $warehouse = $criteria['warehouse'];
                    /** @var ProductUnitPrecision $precision */
                    $precision = $criteria['productUnitPrecision'];
                    foreach ($existingLevels as $level) {
                        if ($level->getWarehouse()->getId() === $warehouse->getId() &&
                            $level->getProductUnitPrecision()->getId() === $precision->getId()
                        ) {
                            return $level;
                        }
                    }
                    return null;
                }
            );

        $this->manager->expects($this->any())
            ->method('getRepository')
            ->with('OroWarehouseBundle:InventoryLevel')
            ->willReturn($repository);

        // mock remove and persist behaviour
        $persistedEntities = [];
        $removedEntities = [];

        $this->manager->expects($this->any())
            ->method('persist')
            ->with($this->isInstanceOf('Oro\Bundle\WarehouseBundle\Entity\InventoryLevel'))
            ->willReturnCallback(
                function ($entity) use (&$persistedEntities) {
                    $persistedEntities[] = $entity;
                }
            );
        $this->manager->expects($this->any())
            ->method('remove')
            ->with($this->isInstanceOf('Oro\Bundle\WarehouseBundle\Entity\InventoryLevel'))
            ->willReturnCallback(
                function ($entity) use (&$removedEntities) {
                    $removedEntities[] = $entity;
                }
            );

        $this->manager->expects($formData && count($formData) ? $this->once() : $this->never())
            ->method('flush');

        $this->roundingService->expects($this->any())
            ->method('round')
            ->willReturnCallback('round');

        $this->handler->process();

        foreach ($expectedLevels as $expectedLevel) {
            /** @var InventoryLevel $entity */
            $entity = $expectedLevel['entity'];
            if (!empty($expectedLevel['persisted'])) {
                $this->assertEquals($entity, $this->findLevelById($persistedEntities, $entity->getId()));
            } elseif (!empty($expectedLevel['removed'])) {
                $this->assertEquals($entity, $this->findLevelById($removedEntities, $entity->getId()));
            } else {
                $this->assertEquals($entity, $this->findLevelById($existingLevels, $entity->getId()));
            }
        }
    }

    /**
     * @return array
     */
    public function processDataProvider()
    {
        return [
            'no data' => [
                'formData' => null,
                'existingLevels' => [],
                'expectedLevels' => [],
            ],
            'empty data' => [
                'formData' => new ArrayCollection([]),
                'existingLevels' => [],
                'expectedLevels' => [],
            ],
            'updated entities' => [
                'formData' => new ArrayCollection([
                    '1_1' => [
                        'warehouse' => $this->createWarehouse(1),
                        'precision' => $this->createPrecision(1),
                        'data' => ['levelQuantity' => 11],
                    ],
                    '2_2' => [
                        'warehouse' => $this->createWarehouse(2),
                        'precision' => $this->createPrecision(2),
                        'data' => ['levelQuantity' => 21],
                    ],
                    '3_3' => [
                        'warehouse' => $this->createWarehouse(3),
                        'precision' => $this->createPrecision(3),
                        'data' => ['levelQuantity' => 30],
                    ],
                ]),
                'existingLevels' => [
                    $this->createLevel(101, 1, 1, 10),
                    $this->createLevel(102, 2, 2, 20),
                    $this->createLevel(103, 3, 3, 30),
                ],
                'expectedLevels' => [
                    ['entity' => $this->createLevel(101, 1, 1, 11)],
                    ['entity' => $this->createLevel(102, 2, 2, 21)],
                    ['entity' => $this->createLevel(103, 3, 3, 30)],
                ]
            ],
            'removed and persisted entities' => [
                'formData' => new ArrayCollection([
                    '1_1' => [
                        'warehouse' => $this->createWarehouse(1),
                        'precision' => $this->createPrecision(1),
                        'data' => ['levelQuantity' => null],
                    ],
                    '2_2' => [
                        'warehouse' => $this->createWarehouse(2),
                        'precision' => $this->createPrecision(2),
                        'data' => ['levelQuantity' => 0],
                    ],
                    '3_3' => [
                        'warehouse' => $this->createWarehouse(3),
                        'precision' => $this->createPrecision(3),
                        'data' => ['levelQuantity' => 31],
                    ],
                ]),
                'existingLevels' => [
                    $this->createLevel(101, 1, 1, 10),
                    $this->createLevel(102, 2, 2, 20),
                ],
                'expectedLevels' => [
                    ['entity' => $this->createLevel(101, 1, 1, 0), 'removed' => true],
                    ['entity' => $this->createLevel(102, 2, 2, 0), 'removed' => true],
                    ['entity' => $this->createLevel(null, 3, 3, 31), 'persisted' => true],
                ]
            ],
            'quantity rounding' => [
                'formData' => new ArrayCollection([
                    '1_1' => [
                        'warehouse' => $this->createWarehouse(1),
                        'precision' => $this->createPrecision(1, 2),
                        'data' => ['levelQuantity' => 10.1234],
                    ],
                ]),
                'existingLevels' => [
                    $this->createLevel(101, 1, 1, 10, 2),
                ],
                'expectedLevels' => [
                    ['entity' => $this->createLevel(101, 1, 1, 10.12, 2)],
                ]
            ],
        ];
    }

    /**
     * @param int $id
     * @return Warehouse
     */
    protected function createWarehouse($id)
    {
        return $this->getEntity('Oro\Bundle\WarehouseBundle\Entity\Warehouse', ['id' => $id]);
    }

    /**
     * @param int $id
     * @param int $precision
     * @return ProductUnitPrecision
     */
    protected function createPrecision($id, $precision = 0)
    {
        return $this->getEntity(
            'Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision',
            ['id' => $id, 'product' => new Product(), 'precision' => $precision]
        );
    }

    /**
     * @param int $warehouseId
     * @param int $precisionId
     * @param int|float $quantity
     * @param int $precision
     * @return InventoryLevel
     */
    protected function createLevel($id, $warehouseId, $precisionId, $quantity, $precision = 0)
    {
        return $this->getEntity(
            'Oro\Bundle\InventoryBundle\Entity\InventoryLevel',
            [
                'id' => $id,
                'warehouse' => $this->createWarehouse($warehouseId),
                'productUnitPrecision' => $this->createPrecision($precisionId, $precision),
                'quantity' => $quantity,
            ]
        );
    }

    /**
     * @param InventoryLevel[] $levels
     * @param int $id
     * @return InventoryLevel|null
     */
    protected function findLevelById(array $levels, $id)
    {
        foreach ($levels as $level) {
            if ($level->getId() === $id) {
                return $level;
            }
        }

        return null;
    }
}
