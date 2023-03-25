<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\Service;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\CollectionSortOrder;
use Oro\Bundle\ProductBundle\Entity\Repository\CollectionSortOrderRepository;
use Oro\Bundle\ProductBundle\Service\ProductCollectionDefinitionConverter as Converter;
use Oro\Bundle\ProductBundle\Service\ProductCollectionSegmentManipulator;
use Oro\Bundle\SegmentBundle\Tests\Unit\Stub\Entity\SegmentStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductCollectionSegmentManipulatorTest extends TestCase
{
    private Converter|MockObject $definitionConverter;

    private ProductCollectionSegmentManipulator $manipulator;

    private CollectionSortOrderRepository|MockObject $sortOrderRepo;

    protected function setUp(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->definitionConverter = $this->createMock(Converter::class);

        $this->manipulator = new ProductCollectionSegmentManipulator(
            $managerRegistry,
            $this->definitionConverter
        );

        $this->sortOrderRepo = $this->createMock(CollectionSortOrderRepository::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getRepository')
            ->with(CollectionSortOrder::class)
            ->willReturn($this->sortOrderRepo);
    }

    /**
     * @dataProvider updateManuallyManagedProductsDataProvider
     *
     * @param int[] $existingIncluded
     * @param int[] $existingExcluded
     * @param int[] $appendProductsIds
     * @param int[] $removeProductsIds
     * @param int[] $expectedExcluded
     * @param int[] $expectedIncluded
     */
    public function testUpdateManuallyManagedProducts(
        array $existingIncluded,
        array $existingExcluded,
        array $appendProductsIds,
        array $removeProductsIds,
        array $expectedIncluded,
        array $expectedExcluded
    ): void {
        $definitionParts = [
            Converter::DEFINITION_KEY => 'sample-converted-definition',
            Converter::INCLUDED_FILTER_KEY => implode(',', $existingIncluded),
            Converter::EXCLUDED_FILTER_KEY => implode(',', $existingExcluded),
        ];

        $segment = (new SegmentStub(42))
            ->setDefinition('sample-definition');
        $this->definitionConverter
            ->expects(self::once())
            ->method('getDefinitionParts')
            ->with($segment->getDefinition())
            ->willReturn($definitionParts);

        $updatedDefinition = 'sample-updated-definition';
        $this->definitionConverter
            ->expects(self::once())
            ->method('putConditionsInDefinition')
            ->with(
                $definitionParts[Converter::DEFINITION_KEY],
                implode(',', $expectedExcluded),
                implode(',', $expectedIncluded)
            )
            ->willReturn($updatedDefinition);

        $this->sortOrderRepo
            ->expects(self::once())
            ->method('removeBySegmentAndProductIds')
            ->with($segment->getId(), $expectedExcluded);

        self::assertEquals(
            [$expectedIncluded, $expectedExcluded],
            $this->manipulator->updateManuallyManagedProducts($segment, $appendProductsIds, $removeProductsIds)
        );

        self::assertEquals($updatedDefinition, $segment->getDefinition());
    }

    public function updateManuallyManagedProductsDataProvider(): array
    {
        return [
            'empty' => [
                'existingIncluded' => [],
                'existingExcluded' => [],
                'appendProductsIds' => [],
                'removeProductsIds' => [],
                'expectedIncluded' => [],
                'expectedExcluded' => [],
            ],
            'append product when empty' => [
                'existingIncluded' => [],
                'existingExcluded' => [],
                'appendProductsIds' => [10, 20],
                'removeProductsIds' => [],
                'expectedIncluded' => [10, 20],
                'expectedExcluded' => [],
            ],
            'remove product when empty' => [
                'existingIncluded' => [],
                'existingExcluded' => [],
                'appendProductsIds' => [],
                'removeProductsIds' => [30, 40],
                'expectedIncluded' => [],
                'expectedExcluded' => [30, 40],
            ],
            'with existing data' => [
                'existingIncluded' => [10, 20],
                'existingExcluded' => [30, 40],
                'appendProductsIds' => [],
                'removeProductsIds' => [],
                'expectedIncluded' => [10, 20],
                'expectedExcluded' => [30, 40],
            ],
            'append product' => [
                'existingIncluded' => [10, 20],
                'existingExcluded' => [30, 40],
                'appendProductsIds' => [50],
                'removeProductsIds' => [],
                'expectedIncluded' => [10, 20, 50],
                'expectedExcluded' => [30, 40],
            ],
            'remove product' => [
                'existingIncluded' => [10, 20],
                'existingExcluded' => [30, 40],
                'appendProductsIds' => [],
                'removeProductsIds' => [50],
                'expectedIncluded' => [10, 20],
                'expectedExcluded' => [30, 40, 50],
            ],
            'appended product is removed from excluded' => [
                'existingIncluded' => [10, 20],
                'existingExcluded' => [30, 40],
                'appendProductsIds' => [30],
                'removeProductsIds' => [],
                'expectedIncluded' => [10, 20, 30],
                'expectedExcluded' => [40],
            ],
            'remove product is takes precedence over added' => [
                'existingIncluded' => [10, 20],
                'existingExcluded' => [30, 40],
                'appendProductsIds' => [30],
                'removeProductsIds' => [30],
                'expectedIncluded' => [10, 20],
                'expectedExcluded' => [40, 30],
            ],
            'removed product is removed from included' => [
                'existingIncluded' => [10, 20],
                'existingExcluded' => [30, 40],
                'appendProductsIds' => [],
                'removeProductsIds' => [20],
                'expectedIncluded' => [10],
                'expectedExcluded' => [30, 40, 20],
            ],
            'uniqueness is ensured in included products' => [
                'existingIncluded' => [10, 20],
                'existingExcluded' => [30, 40],
                'appendProductsIds' => [10, 20],
                'removeProductsIds' => [30, 40],
                'expectedIncluded' => [10, 20],
                'expectedExcluded' => [30, 40],
            ],
        ];
    }
}
