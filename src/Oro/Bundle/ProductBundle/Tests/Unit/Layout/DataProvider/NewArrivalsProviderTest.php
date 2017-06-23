<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\NewArrivalsProvider;
use Oro\Bundle\ProductBundle\Provider\Segment\ProductSegmentProviderInterface;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class NewArrivalsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NewArrivalsProvider
     */
    private $provider;

    /**
     * @var SegmentManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $segmentManager;

    /**
     * @var ProductSegmentProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productSegmentProvider;

    /**
     * @var ProductManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productManager;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configManager;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->segmentManager = $this->createMock(SegmentManager::class);
        $this->productSegmentProvider = $this->createMock(ProductSegmentProviderInterface::class);
        $this->productManager = $this->createMock(ProductManager::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new NewArrivalsProvider(
            $this->segmentManager,
            $this->productSegmentProvider,
            $this->productManager,
            $this->configManager
        );
    }

    /**
     * @dataProvider getNewArrivalsMixAndMaxInvalidDataProvider
     *
     * @param int $maxLimit
     * @param int $minLimit
     */
    public function testGetNewArrivalsMixAndMaxInvalid($maxLimit, $minLimit)
    {
        $this->configManager
            ->method('get')
            ->will(static::returnValueMap([
                ['oro_product.new_arrivals_max_items', false, false, null, $maxLimit],
                ['oro_product.new_arrivals_min_items', false, false, null, $minLimit],
            ]));

        $this->productSegmentProvider->expects(static::never())
            ->method('getProductSegmentById');

        static::assertEquals([], $this->provider->getNewArrivals());
    }

    /**
     * @return array
     */
    public function getNewArrivalsMixAndMaxInvalidDataProvider()
    {
        return [
            [
                'max' => 0,
                'min' => 1,
            ],
            [
                'max' => 0,
                'min' => 0,
            ],
        ];
    }

    public function testGetNewArrivalsNoSegment()
    {
        $this->configManager
            ->method('get')
            ->will(static::returnValueMap([
                ['oro_product.new_arrivals_products_segment_id', false, false, null, 1],
                ['oro_product.new_arrivals_max_items', false, false, null, 1],
                ['oro_product.new_arrivals_min_items', false, false, null, 1],
            ]));

        $this->productSegmentProvider->expects(static::once())
            ->method('getProductSegmentById')
            ->with(1)
            ->willReturn(null);

        static::assertEquals([], $this->provider->getNewArrivals());
    }

    public function testGetNewArrivalsNoSegmentInConfig()
    {
        $this->configManager
            ->method('get')
            ->will(static::returnValueMap([
                ['oro_product.new_arrivals_products_segment_id', false, false, null, null],
                ['oro_product.new_arrivals_max_items', false, false, null, 1],
                ['oro_product.new_arrivals_min_items', false, false, null, 1],
            ]));

        $this->productSegmentProvider->expects(static::never())
            ->method('getProductSegmentById');

        static::assertEquals([], $this->provider->getNewArrivals());
    }

    public function testGetNewArrivalsNoQueryBuilder()
    {
        $segment = $this->createMock(Segment::class);

        $this->configManager
            ->method('get')
            ->will(static::returnValueMap([
                ['oro_product.new_arrivals_products_segment_id', false, false, null, 2],
                ['oro_product.new_arrivals_max_items', false, false, null, 4],
                ['oro_product.new_arrivals_min_items', false, false, null, 2],
            ]));

        $this->productSegmentProvider->expects(static::once())
            ->method('getProductSegmentById')
            ->with(2)
            ->willReturn($segment);

        $this->segmentManager->expects(static::once())
            ->method('getEntityQueryBuilder')
            ->with($segment)
            ->willReturn(null);

        static::assertEquals([], $this->provider->getNewArrivals());
    }

    /**
     * @dataProvider getNewArrivalsDataProvider
     *
     * @param int   $maxLimit
     * @param int   $minLimit
     * @param array $products
     * @param array $expectedProducts
     */
    public function testGetNewArrivals($maxLimit, $minLimit, array $products, array $expectedProducts)
    {
        $segment = $this->createMock(Segment::class);

        $this->configManager
            ->method('get')
            ->will(static::returnValueMap([
                ['oro_product.new_arrivals_products_segment_id', false, false, null, 1],
                ['oro_product.new_arrivals_max_items', false, false, null, $maxLimit],
                ['oro_product.new_arrivals_min_items', false, false, null, $minLimit],
            ]));

        $this->productSegmentProvider->expects(static::once())
            ->method('getProductSegmentById')
            ->with(1)
            ->willReturn($segment);

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->segmentManager->expects(static::once())
            ->method('getEntityQueryBuilder')
            ->with($segment)
            ->willReturn($queryBuilder);

        $this->productManager->expects(static::once())
            ->method('restrictQueryBuilder')
            ->with($queryBuilder, [])
            ->willReturn($queryBuilder);

        $queryBuilder->expects(static::once())
            ->method('setMaxResults')
            ->with($maxLimit)
            ->willReturn($queryBuilder);

        $query = $this->createMock(AbstractQuery::class);

        $queryBuilder->expects(static::once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects(static::once())
            ->method('getResult')
            ->willReturn($products);

        static::assertEquals($expectedProducts, $this->provider->getNewArrivals());
    }

    /**
     * @return array
     */
    public function getNewArrivalsDataProvider()
    {
        $product1 = $this->createMock(Product::class);
        $product2 = $this->createMock(Product::class);

        return [
            'null min limit' => [
                'maxLimit' => 4,
                'minLimit' => null,
                'products' => [
                    $product1,
                    $product2,
                ],
                'expectedProducts' => [
                    $product1,
                    $product2,
                ],
            ],
            'min limit' => [
                'maxLimit' => 4,
                'minLimit' => 2,
                'products' => [
                    $product1,
                    $product2,
                ],
                'expectedProducts' => [
                    $product1,
                    $product2,
                ],
            ],
            'less then in min limit' => [
                'maxLimit' => 3,
                'minLimit' => 3,
                'products' => [
                    $product1,
                    $product2,
                ],
                'expectedProducts' => [],
            ],
        ];
    }

    public function testGetNewArrivalsNullMaxLimit()
    {
        $segment = $this->createMock(Segment::class);

        $this->configManager
            ->method('get')
            ->will(static::returnValueMap([
                ['oro_product.new_arrivals_products_segment_id', false, false, null, 1],
                ['oro_product.new_arrivals_max_items', false, false, null, null],
                ['oro_product.new_arrivals_min_items', false, false, null, 2],
            ]));

        $this->productSegmentProvider->expects(static::once())
            ->method('getProductSegmentById')
            ->with(1)
            ->willReturn($segment);

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->segmentManager->expects(static::once())
            ->method('getEntityQueryBuilder')
            ->with($segment)
            ->willReturn($queryBuilder);

        $this->productManager->expects(static::once())
            ->method('restrictQueryBuilder')
            ->with($queryBuilder, [])
            ->willReturn($queryBuilder);

        $queryBuilder->expects(static::never())
            ->method('setMaxResults');

        $query = $this->createMock(AbstractQuery::class);

        $queryBuilder->expects(static::once())
            ->method('getQuery')
            ->willReturn($query);

        $product1 = $this->createMock(Product::class);
        $product2 = $this->createMock(Product::class);

        $query->expects(static::once())
            ->method('getResult')
            ->willReturn([
                $product1,
                $product2,
            ]);

        static::assertEquals([
            $product1,
            $product2,
        ], $this->provider->getNewArrivals());
    }
}
