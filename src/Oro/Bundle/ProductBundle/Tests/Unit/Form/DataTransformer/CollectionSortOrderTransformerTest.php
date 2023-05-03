<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\CollectionSortOrder;
use Oro\Bundle\ProductBundle\Entity\Product as ProductEntity;
use Oro\Bundle\ProductBundle\Form\DataTransformer\CollectionSortOrderTransformer;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class CollectionSortOrderTransformerTest extends \PHPUnit\Framework\TestCase
{
    private DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper;

    protected CollectionSortOrderTransformer $transformer;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->transformer = new CollectionSortOrderTransformer(
            $this->doctrineHelper,
            new Segment()
        );
    }

    /**
     * @dataProvider transformDataProvider
     */
    public function testTransformData(mixed $expected, mixed $value)
    {
        $this->assertSame($expected, $this->transformer->transform($value));
    }

    public function transformDataProvider(): array
    {
        return [
            [null, null],
            [[], []],
            [
                [
                    1 => ['data' => ['categorySortOrder' => 0.1]],
                    2 => ['data' => ['categorySortOrder' => 0.2]]
                ],
                [
                    1 => ['data' => $this->createDataObject(1, 0.1)],
                    2 => ['data' => $this->createDataObject(2, 0.2)]
                ]
            ]
        ];
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransformData(mixed $expected, mixed $value)
    {
        $entityManager = $this->createMock(EntityManager::class);
        $this->doctrineHelper->expects(empty($expected) ? $this->never() : $this->exactly(count($expected)))
            ->method('getEntityManager')
            ->with(ProductEntity::class)
            ->willReturn($entityManager);

        $entityManager
            ->expects(self::any())
            ->method('find')
            ->willReturnCallback(function () {
                return $this->createProductObject(func_get_arg(1));
            });

        $repository = $this->createMock(EntityRepository::class);
        $this->doctrineHelper->expects(empty($expected) ? $this->never() : $this->exactly(count($expected)))
            ->method('getEntityRepository')
            ->with(CollectionSortOrder::class)
            ->willReturn($repository);

        $this->assertEquals($expected, $this->transformer->reverseTransform($value));
    }

    public function reverseTransformDataProvider(): array
    {
        return [
            [new ArrayCollection(), null],
            [[], new ArrayCollection()],
            [
                [
                    1 => ['data' => $this->createDataObject(1, 0.1)],
                    2 => ['data' => $this->createDataObject(2, 0.2)]
                ],
                new ArrayCollection([
                    1 => ['data' => ['categorySortOrder' => 0.1]],
                    2 => ['data' => ['categorySortOrder' => 0.2]]
                ])
            ]
        ];
    }

    public function testReverseTransformException()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "array", "string" given');

        $this->transformer->reverseTransform('test');
    }

    private function createDataObject(int $productId, float $sortOrder): CollectionSortOrder
    {
        $obj = new CollectionSortOrder();
        $obj->setProduct($this->createProductObject($productId));
        $obj->setSegment(new Segment());
        $obj->setSortOrder($sortOrder);

        return $obj;
    }

    private function createProductObject(int $id): Product
    {
        $obj = new Product();
        $obj->__set('id', $id);

        return $obj;
    }
}
