<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\NewArrivalsProvider;
use Oro\Bundle\ProductBundle\Layout\SegmentProducts\SegmentProductsQueryProvider;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ProductBundle\Provider\ProductListBuilder;
use Oro\Bundle\ProductBundle\Provider\ProductSegmentProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class NewArrivalsProviderTest extends OrmTestCase
{
    private const PRODUCT_LIST_TYPE = 'new_arrivals';

    /** @var EntityManagerInterface */
    private $em;

    /** @var SegmentProductsQueryProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $segmentProductsQueryProvider;

    /** @var ProductSegmentProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productSegmentProvider;

    /** @var ProductListBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $productListBuilder;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var NewArrivalsProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));

        $this->segmentProductsQueryProvider = $this->createMock(SegmentProductsQueryProvider::class);
        $this->productSegmentProvider = $this->createMock(ProductSegmentProvider::class);
        $this->productListBuilder = $this->createMock(ProductListBuilder::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new NewArrivalsProvider(
            $this->segmentProductsQueryProvider,
            $this->productSegmentProvider,
            $this->productListBuilder,
            $this->aclHelper,
            $this->configManager
        );
    }

    private function getSegment(int $id): Segment
    {
        $segment = new Segment();
        ReflectionUtil::setId($segment, $id);
        $segment->setRecordsLimit(25);

        return $segment;
    }

    private function getQuery(): Query
    {
        return $this->em->createQuery('SELECT p.id FROM ' . Product::class . ' p');
    }

    private function getProductView(int $id): ProductView
    {
        $productView = new ProductView();
        $productView->set('id', $id);

        return $productView;
    }

    public function testGetProducts(): void
    {
        $segment = $this->getSegment(42);
        $minItemsLimit = 1;
        $maxItemsLimit = 4;
        $productId = 100;

        $this->configManager->expects(self::exactly(3))
            ->method('get')
            ->willReturnMap([
                ['oro_product.new_arrivals_min_items', false, false, null, $minItemsLimit],
                ['oro_product.new_arrivals_max_items', false, false, null, $maxItemsLimit],
                ['oro_product.new_arrivals_products_segment_id', false, false, null, $segment->getId()]
            ]);
        $this->productSegmentProvider->expects(self::once())
            ->method('getProductSegmentById')
            ->with($segment->getId())
            ->willReturn($segment);

        $query = $this->getQuery();
        $this->segmentProductsQueryProvider->expects(self::once())
            ->method('getQuery')
            ->with(self::identicalTo($segment), self::PRODUCT_LIST_TYPE)
            ->willReturn($query);
        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with(self::identicalTo($query))
            ->willReturnArgument(0);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            sprintf('SELECT o0_.id AS id_0 FROM oro_product o0_ LIMIT %d', $maxItemsLimit),
            [['id_0' => $productId]]
        );

        $productViews = [$this->getProductView($productId)];
        $this->productListBuilder->expects(self::once())
            ->method('getProductsByIds')
            ->with(self::PRODUCT_LIST_TYPE, [$productId])
            ->willReturn($productViews);

        $this->assertEquals($productViews, $this->provider->getProducts());
        // test memory cache
        $this->assertEquals($productViews, $this->provider->getProducts());
    }

    public function testGetProductsWhenNoMaxItemsLimit(): void
    {
        $segment = $this->getSegment(42);
        $productId = 100;

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                ['oro_product.new_arrivals_max_items', false, false, null, null],
                ['oro_product.new_arrivals_products_segment_id', false, false, null, $segment->getId()]
            ]);
        $this->productSegmentProvider->expects(self::once())
            ->method('getProductSegmentById')
            ->with($segment->getId())
            ->willReturn($segment);

        $query = $this->getQuery();
        $this->segmentProductsQueryProvider->expects(self::once())
            ->method('getQuery')
            ->with(self::identicalTo($segment), self::PRODUCT_LIST_TYPE)
            ->willReturn($query);
        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with(self::identicalTo($query))
            ->willReturnArgument(0);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            'SELECT o0_.id AS id_0 FROM oro_product o0_',
            [['id_0' => $productId]]
        );

        $productViews = [$this->getProductView($productId)];
        $this->productListBuilder->expects(self::once())
            ->method('getProductsByIds')
            ->with(self::PRODUCT_LIST_TYPE, [$productId])
            ->willReturn($productViews);

        $this->assertEquals($productViews, $this->provider->getProducts());
        // test memory cache
        $this->assertEquals($productViews, $this->provider->getProducts());
    }

    public function testGetProductsWhenNoMinItemsLimit(): void
    {
        $segment = $this->getSegment(42);
        $maxItemsLimit = 4;
        $productId = 100;

        $this->configManager->expects(self::exactly(3))
            ->method('get')
            ->willReturnMap([
                ['oro_product.new_arrivals_min_items', false, false, null, null],
                ['oro_product.new_arrivals_max_items', false, false, null, $maxItemsLimit],
                ['oro_product.new_arrivals_products_segment_id', false, false, null, $segment->getId()]
            ]);
        $this->productSegmentProvider->expects(self::once())
            ->method('getProductSegmentById')
            ->with($segment->getId())
            ->willReturn($segment);

        $query = $this->getQuery();
        $this->segmentProductsQueryProvider->expects(self::once())
            ->method('getQuery')
            ->with(self::identicalTo($segment), self::PRODUCT_LIST_TYPE)
            ->willReturn($query);
        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with(self::identicalTo($query))
            ->willReturnArgument(0);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            sprintf('SELECT o0_.id AS id_0 FROM oro_product o0_ LIMIT %d', $maxItemsLimit),
            [['id_0' => $productId]]
        );

        $productViews = [$this->getProductView($productId)];
        $this->productListBuilder->expects(self::once())
            ->method('getProductsByIds')
            ->with(self::PRODUCT_LIST_TYPE, [$productId])
            ->willReturn($productViews);

        $this->assertEquals($productViews, $this->provider->getProducts());
        // test memory cache
        $this->assertEquals($productViews, $this->provider->getProducts());
    }

    public function testGetProductsWhenInvalidMaxItemsLimit(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_product.new_arrivals_max_items')
            ->willReturn(-1);
        $this->productSegmentProvider->expects(self::never())
            ->method('getProductSegmentById');

        $this->segmentProductsQueryProvider->expects(self::never())
            ->method('getQuery');
        $this->aclHelper->expects(self::never())
            ->method('apply');

        $this->productListBuilder->expects(self::never())
            ->method('getProductsByIds');

        $this->assertSame([], $this->provider->getProducts());
        // test memory cache
        $this->assertSame([], $this->provider->getProducts());
    }

    public function testGetProductsWhenMaxItemsLimitLessThenMinItemsLimit(): void
    {
        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                ['oro_product.new_arrivals_min_items', false, false, null, 2],
                ['oro_product.new_arrivals_max_items', false, false, null, 1]
            ]);
        $this->productSegmentProvider->expects(self::never())
            ->method('getProductSegmentById');

        $this->segmentProductsQueryProvider->expects(self::never())
            ->method('getQuery');
        $this->aclHelper->expects(self::never())
            ->method('apply');

        $this->productListBuilder->expects(self::never())
            ->method('getProductsByIds');

        $this->assertSame([], $this->provider->getProducts());
        // test memory cache
        $this->assertSame([], $this->provider->getProducts());
    }

    public function testGetProductsWhenMaxItemsLimitEqualsToMinItemsLimit(): void
    {
        $segment = $this->getSegment(42);
        $minItemsLimit = 1;
        $maxItemsLimit = 1;
        $productId = 100;

        $this->configManager->expects(self::exactly(3))
            ->method('get')
            ->willReturnMap([
                ['oro_product.new_arrivals_min_items', false, false, null, $minItemsLimit],
                ['oro_product.new_arrivals_max_items', false, false, null, $maxItemsLimit],
                ['oro_product.new_arrivals_products_segment_id', false, false, null, $segment->getId()]
            ]);
        $this->productSegmentProvider->expects(self::once())
            ->method('getProductSegmentById')
            ->with($segment->getId())
            ->willReturn($segment);

        $query = $this->getQuery();
        $this->segmentProductsQueryProvider->expects(self::once())
            ->method('getQuery')
            ->with(self::identicalTo($segment), self::PRODUCT_LIST_TYPE)
            ->willReturn($query);
        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with(self::identicalTo($query))
            ->willReturnArgument(0);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            sprintf('SELECT o0_.id AS id_0 FROM oro_product o0_ LIMIT %d', $maxItemsLimit),
            [['id_0' => $productId]]
        );

        $productViews = [$this->getProductView($productId)];
        $this->productListBuilder->expects(self::once())
            ->method('getProductsByIds')
            ->with(self::PRODUCT_LIST_TYPE, [$productId])
            ->willReturn($productViews);

        $this->assertEquals($productViews, $this->provider->getProducts());
        // test memory cache
        $this->assertEquals($productViews, $this->provider->getProducts());
    }


    public function testGetProductsWhenMinItemsLimitDoesNotReached(): void
    {
        $segment = $this->getSegment(42);
        $minItemsLimit = 2;
        $maxItemsLimit = 4;
        $productId = 100;

        $this->configManager->expects(self::exactly(3))
            ->method('get')
            ->willReturnMap([
                ['oro_product.new_arrivals_min_items', false, false, null, $minItemsLimit],
                ['oro_product.new_arrivals_max_items', false, false, null, $maxItemsLimit],
                ['oro_product.new_arrivals_products_segment_id', false, false, null, $segment->getId()]
            ]);
        $this->productSegmentProvider->expects(self::once())
            ->method('getProductSegmentById')
            ->with($segment->getId())
            ->willReturn($segment);

        $query = $this->getQuery();
        $this->segmentProductsQueryProvider->expects(self::once())
            ->method('getQuery')
            ->with(self::identicalTo($segment), self::PRODUCT_LIST_TYPE)
            ->willReturn($query);
        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with(self::identicalTo($query))
            ->willReturnArgument(0);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            sprintf('SELECT o0_.id AS id_0 FROM oro_product o0_ LIMIT %d', $maxItemsLimit),
            [['id_0' => $productId]]
        );

        $this->productListBuilder->expects(self::never())
            ->method('getProductsByIds');

        $this->assertSame([], $this->provider->getProducts());
        // test memory cache
        $this->assertSame([], $this->provider->getProducts());
    }

    public function testGetProductsWhenNoProducts(): void
    {
        $segment = $this->getSegment(42);
        $minItemsLimit = 1;
        $maxItemsLimit = 4;

        $this->configManager->expects(self::exactly(3))
            ->method('get')
            ->willReturnMap([
                ['oro_product.new_arrivals_min_items', false, false, null, $minItemsLimit],
                ['oro_product.new_arrivals_max_items', false, false, null, $maxItemsLimit],
                ['oro_product.new_arrivals_products_segment_id', false, false, null, $segment->getId()]
            ]);
        $this->productSegmentProvider->expects(self::once())
            ->method('getProductSegmentById')
            ->with($segment->getId())
            ->willReturn($segment);

        $query = $this->getQuery();
        $this->segmentProductsQueryProvider->expects(self::once())
            ->method('getQuery')
            ->with(self::identicalTo($segment), self::PRODUCT_LIST_TYPE)
            ->willReturn($query);
        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with(self::identicalTo($query))
            ->willReturnArgument(0);

        $this->setQueryExpectation(
            $this->getDriverConnectionMock($this->em),
            sprintf('SELECT o0_.id AS id_0 FROM oro_product o0_ LIMIT %d', $maxItemsLimit),
            []
        );

        $this->productListBuilder->expects(self::never())
            ->method('getProductsByIds');

        $this->assertSame([], $this->provider->getProducts());
        // test memory cache
        $this->assertSame([], $this->provider->getProducts());
    }

    public function testGetProductsWhenNoQuery(): void
    {
        $segment = $this->getSegment(42);
        $minItemsLimit = 1;
        $maxItemsLimit = 4;

        $this->configManager->expects(self::exactly(3))
            ->method('get')
            ->willReturnMap([
                ['oro_product.new_arrivals_min_items', false, false, null, $minItemsLimit],
                ['oro_product.new_arrivals_max_items', false, false, null, $maxItemsLimit],
                ['oro_product.new_arrivals_products_segment_id', false, false, null, $segment->getId()]
            ]);
        $this->productSegmentProvider->expects(self::once())
            ->method('getProductSegmentById')
            ->with($segment->getId())
            ->willReturn($segment);
        $this->segmentProductsQueryProvider->expects(self::once())
            ->method('getQuery')
            ->with(self::identicalTo($segment), self::PRODUCT_LIST_TYPE)
            ->willReturn(null);
        $this->aclHelper->expects(self::never())
            ->method('apply');
        $this->productListBuilder->expects(self::never())
            ->method('getProductsByIds');

        $this->assertSame([], $this->provider->getProducts());
        // test memory cache
        $this->assertSame([], $this->provider->getProducts());
    }

    public function testGetProductsWhenNoSegment(): void
    {
        $segmentId = 42;
        $minItemsLimit = 1;
        $maxItemsLimit = 4;

        $this->configManager->expects(self::exactly(3))
            ->method('get')
            ->willReturnMap([
                ['oro_product.new_arrivals_min_items', false, false, null, $minItemsLimit],
                ['oro_product.new_arrivals_max_items', false, false, null, $maxItemsLimit],
                ['oro_product.new_arrivals_products_segment_id', false, false, null, $segmentId]
            ]);
        $this->productSegmentProvider->expects(self::once())
            ->method('getProductSegmentById')
            ->with($segmentId)
            ->willReturn(null);
        $this->segmentProductsQueryProvider->expects(self::never())
            ->method('getQuery');
        $this->aclHelper->expects(self::never())
            ->method('apply');
        $this->productListBuilder->expects(self::never())
            ->method('getProductsByIds');

        $this->assertSame([], $this->provider->getProducts());
        // test memory cache
        $this->assertSame([], $this->provider->getProducts());
    }

    public function testGetProductsWhenNoConfiguredSegmentId(): void
    {
        $minItemsLimit = 1;
        $maxItemsLimit = 4;

        $this->configManager->expects(self::exactly(3))
            ->method('get')
            ->willReturnMap([
                ['oro_product.new_arrivals_min_items', false, false, null, $minItemsLimit],
                ['oro_product.new_arrivals_max_items', false, false, null, $maxItemsLimit],
                ['oro_product.new_arrivals_products_segment_id', false, false, null, null]
            ]);
        $this->productSegmentProvider->expects(self::never())
            ->method('getProductSegmentById');
        $this->segmentProductsQueryProvider->expects(self::never())
            ->method('getQuery');
        $this->aclHelper->expects(self::never())
            ->method('apply');
        $this->productListBuilder->expects(self::never())
            ->method('getProductsByIds');

        $this->assertSame([], $this->provider->getProducts());
        // test memory cache
        $this->assertSame([], $this->provider->getProducts());
    }
}
