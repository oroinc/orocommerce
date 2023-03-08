<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\SegmentProductsProvider;
use Oro\Bundle\ProductBundle\Layout\SegmentProducts\SegmentProductsQueryProvider;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ProductBundle\Provider\ProductListBuilder;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class SegmentProductsProviderTest extends OrmTestCase
{
    private const PRODUCT_LIST_TYPE = 'segment_products';

    /** @var EntityManagerInterface */
    private $em;

    /** @var SegmentProductsQueryProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $segmentProductsQueryProvider;

    /** @var ProductListBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $productListBuilder;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var SegmentProductsProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));

        $this->segmentProductsQueryProvider = $this->createMock(SegmentProductsQueryProvider::class);
        $this->productListBuilder = $this->createMock(ProductListBuilder::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->provider = new SegmentProductsProvider(
            $this->segmentProductsQueryProvider,
            $this->productListBuilder,
            $this->aclHelper
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

        $this->assertEquals($productViews, $this->provider->getProducts($segment, $minItemsLimit, $maxItemsLimit));
        // test memory cache
        $this->assertEquals($productViews, $this->provider->getProducts($segment, $minItemsLimit, $maxItemsLimit));
    }

    public function testGetProductsWhenMaxItemsLimitEqualsToMinItemsLimit(): void
    {
        $segment = $this->getSegment(42);
        $minItemsLimit = 1;
        $maxItemsLimit = 1;
        $productId = 100;

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

        $this->assertEquals($productViews, $this->provider->getProducts($segment, $minItemsLimit, $maxItemsLimit));
        // test memory cache
        $this->assertEquals($productViews, $this->provider->getProducts($segment, $minItemsLimit, $maxItemsLimit));
    }

    public function testGetProductsWhenMinItemsLimitDoesNotReached(): void
    {
        $segment = $this->getSegment(42);
        $minItemsLimit = 2;
        $maxItemsLimit = 4;
        $productId = 100;

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

        $this->assertSame([], $this->provider->getProducts($segment, $minItemsLimit, $maxItemsLimit));
        // test memory cache
        $this->assertSame([], $this->provider->getProducts($segment, $minItemsLimit, $maxItemsLimit));
    }

    public function testGetProductsWhenNoProducts(): void
    {
        $segment = $this->getSegment(42);
        $minItemsLimit = 1;
        $maxItemsLimit = 4;

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

        $this->assertSame([], $this->provider->getProducts($segment, $minItemsLimit, $maxItemsLimit));
        // test memory cache
        $this->assertSame([], $this->provider->getProducts($segment, $minItemsLimit, $maxItemsLimit));
    }

    public function testGetProductsWhenNoQuery(): void
    {
        $segment = $this->getSegment(42);
        $minItemsLimit = 1;
        $maxItemsLimit = 4;

        $this->segmentProductsQueryProvider->expects(self::once())
            ->method('getQuery')
            ->with(self::identicalTo($segment), self::PRODUCT_LIST_TYPE)
            ->willReturn(null);
        $this->aclHelper->expects(self::never())
            ->method('apply');
        $this->productListBuilder->expects(self::never())
            ->method('getProductsByIds');

        $this->assertSame([], $this->provider->getProducts($segment, $minItemsLimit, $maxItemsLimit));
        // test memory cache
        $this->assertSame([], $this->provider->getProducts($segment, $minItemsLimit, $maxItemsLimit));
    }

    public function testGetProductsWhenMaxItemsLimitIsInvalid(): void
    {
        $segment = $this->getSegment(42);
        $minItemsLimit = 1;
        $maxItemsLimit = 0;

        $this->segmentProductsQueryProvider->expects(self::never())
            ->method('getQuery');
        $this->aclHelper->expects(self::never())
            ->method('apply');
        $this->productListBuilder->expects(self::never())
            ->method('getProductsByIds');

        $this->assertSame([], $this->provider->getProducts($segment, $minItemsLimit, $maxItemsLimit));
        // test memory cache
        $this->assertSame([], $this->provider->getProducts($segment, $minItemsLimit, $maxItemsLimit));
    }

    public function testGetProductsWhenMaxItemsLimitIsLessThanMinItemsLimit(): void
    {
        $segment = $this->getSegment(42);
        $minItemsLimit = 5;
        $maxItemsLimit = 4;

        $this->segmentProductsQueryProvider->expects(self::never())
            ->method('getQuery');
        $this->aclHelper->expects(self::never())
            ->method('apply');
        $this->productListBuilder->expects(self::never())
            ->method('getProductsByIds');

        $this->assertSame([], $this->provider->getProducts($segment, $minItemsLimit, $maxItemsLimit));
        // test memory cache
        $this->assertSame([], $this->provider->getProducts($segment, $minItemsLimit, $maxItemsLimit));
    }

    public function testGetProductsShouldNotOverrideAnotherCachedResult(): void
    {
        $segment = $this->getSegment(42);
        $productId = 100;

        $query = $this->getQuery();
        $this->segmentProductsQueryProvider->expects(self::exactly(2))
            ->method('getQuery')
            ->with(self::identicalTo($segment), self::PRODUCT_LIST_TYPE)
            ->willReturn($query);
        $this->aclHelper->expects(self::exactly(2))
            ->method('apply')
            ->with(self::identicalTo($query))
            ->willReturnArgument(0);

        $this->addQueryExpectation(
            'SELECT o0_.id AS id_0 FROM oro_product o0_ LIMIT 4',
            [['id_0' => $productId]]
        );
        $this->addQueryExpectation(
            'SELECT o0_.id AS id_0 FROM oro_product o0_ LIMIT 5',
            [['id_0' => $productId]]
        );
        $this->applyQueryExpectations($this->getDriverConnectionMock($this->em));

        $productViews = [$this->getProductView($productId)];
        $this->productListBuilder->expects(self::exactly(2))
            ->method('getProductsByIds')
            ->with(self::PRODUCT_LIST_TYPE, [$productId])
            ->willReturn($productViews);

        $this->assertEquals($productViews, $this->provider->getProducts($segment, 1, 4));
        $this->assertEquals($productViews, $this->provider->getProducts($segment, 1, 5));
        // test memory cache
        $this->assertEquals($productViews, $this->provider->getProducts($segment, 1, 4));
        $this->assertEquals($productViews, $this->provider->getProducts($segment, 1, 5));
    }
}
