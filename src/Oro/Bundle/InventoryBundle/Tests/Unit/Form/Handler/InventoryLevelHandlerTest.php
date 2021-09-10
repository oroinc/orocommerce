<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Form\Handler\InventoryLevelHandler;
use Oro\Bundle\InventoryBundle\Inventory\InventoryManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class InventoryLevelHandlerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var MockObject|FormInterface
     */
    protected $form;

    /**
     * @var MockObject|ObjectManager
     */
    protected $manager;

    /**
     * @var MockObject|RoundingServiceInterface
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

    /**
     * @var MockObject|InventoryManager
     */
    private $inventoryManager;

    protected function setUp(): void
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->manager = $this->createMock(ObjectManager::class);
        $this->roundingService = $this->createMock(RoundingServiceInterface::class);
        $this->inventoryManager = $this->createMock(InventoryManager::class);
        $this->request = new Request();

        $this->handler = new InventoryLevelHandler(
            $this->form,
            $this->manager,
            $this->request,
            $this->roundingService,
            $this->inventoryManager
        );
    }

    public function testProcessGet()
    {
        $this->form->expects($this->never())
            ->method('handleRequest');

        $this->handler->process();
    }

    public function testProcessInvalidForm()
    {
        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('handleRequest')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
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

        $this->mockForm($formData);

        $persistedEntities = [];
        $removedEntities = [];
        $this->mockManager($existingLevels, $formData, $persistedEntities, $removedEntities);

        $this->roundingService->method('round')
            ->willReturnCallback(function ($value, $precision = null, $roundType = null) {
                return \round($value, $precision ?? 0, $roundType ?? 0);
            });

        $this->handler->process();

        $this->assertExpectedLevels($expectedLevels, $persistedEntities, $removedEntities, $existingLevels);
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
                        'precision' => $this->createPrecision(1),
                        'data' => ['levelQuantity' => 11],
                    ],
                    '2_2' => [
                        'precision' => $this->createPrecision(2),
                        'data' => ['levelQuantity' => 21],
                    ],
                    '3_3' => [
                        'precision' => $this->createPrecision(3),
                        'data' => ['levelQuantity' => 30],
                    ],
                ]),
                'existingLevels' => [
                    $this->createLevel(101, 1, 10),
                    $this->createLevel(102, 2, 20),
                    $this->createLevel(103, 3, 30),
                ],
                'expectedLevels' => [
                    ['entity' => $this->createLevel(101, 1, 11)],
                    ['entity' => $this->createLevel(102, 2, 21)],
                    ['entity' => $this->createLevel(103, 3, 30)],
                ]
            ],
            'quantity rounding' => [
                'formData' => new ArrayCollection([
                    '1_1' => [
                        'precision' => $this->createPrecision(1, 2),
                        'data' => ['levelQuantity' => 10.1234],
                    ],
                ]),
                'existingLevels' => [
                    $this->createLevel(101, 1, 10, 2),
                ],
                'expectedLevels' => [
                    ['entity' => $this->createLevel(101, 1, 10.12, 2)],
                ]
            ],
        ];
    }

    public function testProcessRemovedAndPersistedEntities()
    {
        $precisionId = 3;
        $precisionQuantity = 31;
        $formData = new ArrayCollection([
            '1_1' => [
                'precision' => $this->createPrecision(1),
                'data' => ['levelQuantity' => null]
            ],
            '2_2' => [
                'precision' => $this->createPrecision(2),
                'data' => ['levelQuantity' => 0]
            ],
            '3_3' => [
                'precision' => $this->createPrecision($precisionId),
                'data' => ['levelQuantity' => $precisionQuantity]
            ]
        ]);
        $existingLevels = [
            $this->createLevel(101, 1, 10),
            $this->createLevel(102, 2, 20)
        ];
        $expectedLevels = [
            ['entity' => $this->createLevel(101, 1, 0), 'removed' => true],
            ['entity' => $this->createLevel(102, 2, 0), 'removed' => true],
            ['entity' => $this->createLevel(null, $precisionId, $precisionQuantity), 'persisted' => true]
        ];

        $this->request->setMethod('POST');

        $this->mockForm($formData);

        $persistedEntities = [];
        $removedEntities = [];
        $this->mockManager($existingLevels, $formData, $persistedEntities, $removedEntities);

        // Mock not existed inventory level
        $newInventoryLevel = $this->createLevel(null, $precisionId, $precisionQuantity);
        $this->inventoryManager->expects($this->once())
            ->method('createInventoryLevel')
            ->willReturn($newInventoryLevel);

        $this->roundingService
            ->method('round')
            ->willReturnCallback(function ($value, $precision = null, $roundType = null) {
                return \round($value, $precision ?? 0, $roundType ?? 0);
            });

        $this->handler->process();

        $this->assertExpectedLevels($expectedLevels, $persistedEntities, $removedEntities, $existingLevels);
    }

    /**
     * @param int $id
     * @param int $precision
     * @return ProductUnitPrecision
     */
    protected function createPrecision($id, $precision = 0)
    {
        return $this->getEntity(
            ProductUnitPrecision::class,
            ['id' => $id, 'product' => new Product(), 'precision' => $precision]
        );
    }

    /**
     * @param int $id
     * @param int $precisionId
     * @param int|float $quantity
     * @param int $precision
     * @return InventoryLevel
     */
    protected function createLevel($id, $precisionId, $quantity, $precision = 0)
    {
        return $this->getEntity(
            InventoryLevel::class,
            [
                'id' => $id,
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

    /**
     * @param mixed $formData
     */
    private function mockForm($formData)
    {
        $this->form->expects($this->once())
            ->method('handleRequest')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $this->form->expects($this->once())
            ->method('getData')
            ->willReturn($formData);
    }

    /**
     * Mock repository behaviour
     * @param array $existingLevels
     * @return ObjectRepository|MockObject
     */
    private function mockRepository(array $existingLevels)
    {
        $repository = $this->createMock(ObjectRepository::class);
        $repository->method('findOneBy')
            ->willReturnCallback(
                static function (array $criteria) use ($existingLevels) {
                    /** @var ProductUnitPrecision $precision */
                    $precision = $criteria['productUnitPrecision'];
                    foreach ($existingLevels as $level) {
                        if ($level->getProductUnitPrecision()->getId() === $precision->getId()) {
                            return $level;
                        }
                    }
                    return null;
                }
            );

        return $repository;
    }

    private function assertExpectedLevels(
        array $expectedLevels,
        array $persistedEntities,
        array $removedEntities,
        array $existingLevels
    ) {
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
     * Mock remove and persist behaviour
     * @param InventoryLevel[] $existingLevels
     * @param mixed $formData
     * @param InventoryLevel[] $persistedEntities
     * @param InventoryLevel[] $removedEntities
     */
    private function mockManager(
        array $existingLevels,
        $formData,
        array &$persistedEntities,
        array &$removedEntities
    ) {
        $repository = $this->mockRepository($existingLevels);
        $this->manager->method('getRepository')
            ->with(InventoryLevel::class)
            ->willReturn($repository);
        $this->manager->method('persist')
            ->with($this->isInstanceOf(InventoryLevel::class))
            ->willReturnCallback(
                static function ($entity) use (&$persistedEntities) {
                    $persistedEntities[] = $entity;
                }
            );
        $this->manager->method('remove')
            ->with($this->isInstanceOf(InventoryLevel::class))
            ->willReturnCallback(
                static function ($entity) use (&$removedEntities) {
                    $removedEntities[] = $entity;
                }
            );
        $this->manager->expects($formData && count($formData) ? $this->once() : $this->never())
            ->method('flush');
    }
}
