<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\FeaturedProductsProvider;
use Oro\Bundle\ProductBundle\Layout\SegmentProducts\SegmentProductsQueryProvider;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ProductBundle\Provider\ProductListBuilder;
use Oro\Bundle\ProductBundle\Provider\ProductSegmentProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

class FeaturedProductsProviderTest extends OrmTestCase
{
    private const PRODUCT_LIST_TYPE = 'featured_products';

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

    /** @var FeaturedProductsProvider */
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

        $this->provider = new FeaturedProductsProvider(
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
        $productId = 100;

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_product.featured_products_segment_id')
            ->willReturn($segment->getId());
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

    public function testGetProductsWhenNoProducts(): void
    {
        $segment = $this->getSegment(42);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_product.featured_products_segment_id')
            ->willReturn($segment->getId());
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

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_product.featured_products_segment_id')
            ->willReturn($segment->getId());
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

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_product.featured_products_segment_id')
            ->willReturn($segmentId);
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
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_product.featured_products_segment_id')
            ->willReturn(null);
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
