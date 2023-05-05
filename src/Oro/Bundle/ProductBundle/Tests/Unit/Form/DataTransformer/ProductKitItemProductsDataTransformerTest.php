<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Form\DataTransformer\ProductKitItemProductsDataTransformer;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductProxyStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductKitItemProductsDataTransformerTest extends TestCase
{
    private const MISSING_PRODUCT_ID = PHP_INT_MAX;

    private ProductKitItemProductsDataTransformer $transformer;

    private ProductRepository|MockObject $productRepo;

    protected function setUp(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $entityManager = $this->createMock(EntityManager::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($entityManager);

        $this->productRepo = $this->createMock(ProductRepository::class);
        $entityManager
            ->expects(self::any())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($this->productRepo);

        $entityManager
            ->expects(self::any())
            ->method('getReference')
            ->willReturnCallback(static fn (string $class, int $id) => new ProductProxyStub($id));

        $this->transformer = new ProductKitItemProductsDataTransformer($managerRegistry);
    }

    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(mixed $value, ?array $expected): void
    {
        self::assertEquals($expected, $this->transformer->transform($value));
    }

    public function transformDataProvider(): array
    {
        return [
            'not collection' => ['value' => null, 'expected' => null],
            'empty collection' => ['value' => new ArrayCollection(), 'expected' => []],
            'KitItemProduct with product should be skipped' => [
                'value' => new ArrayCollection(
                    [new ProductKitItemProduct()]
                ),
                'expected' => []
            ],
            'not empty collection ' => [
                'value' => new ArrayCollection(
                    [
                        (new ProductKitItemProduct())
                            ->setProduct((new ProductStub())->setId(42))
                            ->setSortOrder(11)
                    ]
                ),
                'expected' => [['productId' => 42, 'sortOrder' => 11]]
            ],
        ];
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(mixed $value, ?Collection $initialValue, ?Collection $expected): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $productIds = [];
        $this->productRepo
            ->expects(self::any())
            ->method('getProductsQueryBuilder')
            ->willReturnCallback(static function () use ($queryBuilder, &$productIds) {
                $productIds = func_get_arg(0);

                return $queryBuilder;
            });

        $queryBuilder
            ->expects(self::any())
            ->method('indexBy')
            ->with('p', 'p.id')
            ->willReturnSelf();

        $query = $this->createMock(AbstractQuery::class);
        $queryBuilder
            ->expects(self::any())
            ->method('getQuery')
            ->willReturn($query);

        $query
            ->expects(self::any())
            ->method('getResult')
            ->willReturnCallback(
                static function () use (&$productIds) {
                    return array_replace(
                        [],
                        ...array_map(
                            static fn (int $id) => [$id => (new ProductStub())->setId($id)],
                            array_filter($productIds, static fn (int $id) => $id != self::MISSING_PRODUCT_ID)
                        )
                    );
                }
            );

        $this->transformer->transform($initialValue);

        self::assertEquals($expected, $this->transformer->reverseTransform($value));
    }

    public function reverseTransformDataProvider(): array
    {
        return [
            'value is not array' => [
                'value' => null,
                'initialValue' => null,
                'expected' => null,
            ],
            'invalid array structure' => [
                'value' => [['foo' => 'bar'], 'baz' => 'bal'],
                'initialValue' => null,
                'expected' => new ArrayCollection(),
            ],
            'single existing product with missing sort order' => [
                'value' => [['productId' => 10]],
                'initialValue' => null,
                'expected' => new ArrayCollection(
                    [(new ProductKitItemProduct())->setProduct((new ProductStub())->setId(10))]
                ),
            ],
            'single existing product with invalid sort order' => [
                'value' => [['productId' => 10, 'sortOrder' => 'foobar']],
                'initialValue' => null,
                'expected' => new ArrayCollection(
                    [(new ProductKitItemProduct())->setProduct((new ProductStub())->setId(10))->setSortOrder(0)]
                ),
            ],
            'single existing product' => [
                'value' => [['productId' => 10, 'sortOrder' => 11]],
                'initialValue' => null,
                'expected' => new ArrayCollection(
                    [(new ProductKitItemProduct())->setProduct((new ProductStub())->setId(10))->setSortOrder(11)]
                ),
            ],
            'single missing product' => [
                'value' => [['productId' => PHP_INT_MAX, 'sortOrder' => 111]],
                'initialValue' => null,
                'expected' => new ArrayCollection(
                    [(new ProductKitItemProduct())->setProduct(new ProductProxyStub(PHP_INT_MAX))->setSortOrder(111)]
                ),
            ],
            'both existing and missing products' => [
                'value' => [['productId' => 10, 'sortOrder' => 11], ['productId' => PHP_INT_MAX, 'sortOrder' => 111]],
                'initialValue' => null,
                'expected' => new ArrayCollection([
                    (new ProductKitItemProduct())->setProduct((new ProductStub())->setId(10))->setSortOrder(11),
                    (new ProductKitItemProduct())->setProduct(new ProductProxyStub(PHP_INT_MAX))->setSortOrder(111)
                ]),
            ],
            'new item is added to initial collection' => [
                'value' => [['productId' => 10, 'sortOrder' => 11], ['productId' => 20, 'sortOrder' => 22]],
                'initialValue' => new ArrayCollection([
                    (new ProductKitItemProduct())->setProduct((new ProductStub())->setId(10))->setSortOrder(11),
                ]),
                'expected' => new ArrayCollection([
                    (new ProductKitItemProduct())->setProduct((new ProductStub())->setId(10))->setSortOrder(11),
                    (new ProductKitItemProduct())->setProduct((new ProductStub())->setId(20))->setSortOrder(22),
                ]),
            ],
            'missing item is removed from initial collection' => [
                'value' => [['productId' => 10, 'sortOrder' => 11]],
                'initialValue' => new ArrayCollection([
                    (new ProductKitItemProduct())->setProduct((new ProductStub())->setId(10))->setSortOrder(11),
                ]),
                'expected' => new ArrayCollection([
                    0 => (new ProductKitItemProduct())->setProduct((new ProductStub())->setId(10))->setSortOrder(11),
                ]),
            ],
            'empty value removes all items from collection' => [
                'value' => [],
                'initialValue' => new ArrayCollection([
                    (new ProductKitItemProduct())->setProduct((new ProductStub())->setId(10))->setSortOrder(11),
                    (new ProductKitItemProduct())->setProduct((new ProductStub())->setId(20))->setSortOrder(22),
                ]),
                'expected' => new ArrayCollection([]),
            ],
        ];
    }
}
