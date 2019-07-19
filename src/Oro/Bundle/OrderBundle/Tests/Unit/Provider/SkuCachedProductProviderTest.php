<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\OrderBundle\Provider\SkuCachedProductProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class SkuCachedProductProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $productRepository;

    /**
     * @var AclHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $aclHelper;

    /**
     * @var SkuCachedProductProvider
     */
    protected $testedProvider;

    public function setUp()
    {
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->testedProvider = new SkuCachedProductProvider($this->productRepository, $this->aclHelper);
    }

    /**
     * @return Product|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createProductMock()
    {
        return $this->createMock(Product::class);
    }

    public function testSuccessfulCaching()
    {
        $skuOne = '1';
        $skuTwo = '2';
        $productMockOne = $this->createProductMock();
        $productMockTwo = $this->createProductMock();

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$productMockOne, $productMockTwo]);

        $this->productRepository
            ->expects($this->once())
            ->method('getBySkuQueryBuilder')
            ->with([$skuOne, $skuTwo])
            ->willReturn($qb);

        $this->aclHelper
            ->expects($this->once())
            ->method('apply')
            ->with($qb)
            ->willReturn($query);

        $productMockOne
            ->expects($this->once())
            ->method('getSku')
            ->willReturn($skuOne);

        $productMockTwo
            ->expects($this->once())
            ->method('getSku')
            ->willReturn($skuTwo);

        $this->testedProvider->addSkuToCache($skuOne);
        $this->testedProvider->addSkuToCache($skuTwo);

        $firstActualProduct = $this->testedProvider->getBySku($skuOne);
        $secondActualProduct = $this->testedProvider->getBySku($skuTwo);

        $this->assertEquals($productMockOne, $firstActualProduct);
        $this->assertEquals($productMockTwo, $secondActualProduct);
    }

    public function testNoSkuInCache()
    {
        $sku = '1';
        $productMock = $this->createProductMock();

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->exactly(2))
            ->method('getOneOrNullResult')
            ->willReturn($productMock);

        $this->productRepository
            ->expects($this->exactly(2))
            ->method('getBySkuQueryBuilder')
            ->with($sku)
            ->willReturn($qb);

        $this->aclHelper
            ->expects($this->exactly(2))
            ->method('apply')
            ->with($qb)
            ->willReturn($query);

        $actualProduct = $this->testedProvider->getBySku($sku);

        $this->assertEquals($productMock, $actualProduct);

        $actualProduct = $this->testedProvider->getBySku($sku);

        $this->assertEquals($productMock, $actualProduct);
    }

    public function testNoProductsInCache()
    {
        $skuOne = '1';
        $skuTwo = '2';

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->productRepository
            ->expects($this->once())
            ->method('getBySkuQueryBuilder')
            ->with([$skuOne, $skuTwo])
            ->willReturn($qb);

        $this->aclHelper
            ->expects($this->once())
            ->method('apply')
            ->with($qb)
            ->willReturn($query);

        $this->testedProvider->addSkuToCache($skuOne);
        $this->testedProvider->addSkuToCache($skuTwo);

        $firstActualProduct = $this->testedProvider->getBySku($skuOne);
        $secondActualProduct = $this->testedProvider->getBySku($skuTwo);

        $this->assertEquals(null, $firstActualProduct);
        $this->assertEquals(null, $secondActualProduct);
    }

    public function testOneProductInCache()
    {
        $skuOne = '1';
        $skuTwo = '2';
        $productMockOne = $this->createProductMock();

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$productMockOne]);

        $this->productRepository
            ->expects($this->once())
            ->method('getBySkuQueryBuilder')
            ->with([$skuOne, $skuTwo])
            ->willReturn($qb);

        $this->aclHelper
            ->expects($this->once())
            ->method('apply')
            ->with($qb)
            ->willReturn($query);

        $productMockOne
            ->expects($this->once())
            ->method('getSku')
            ->willReturn($skuOne);

        $this->testedProvider->addSkuToCache($skuOne);
        $this->testedProvider->addSkuToCache($skuTwo);

        $firstActualProduct = $this->testedProvider->getBySku($skuOne);
        $secondActualProduct = $this->testedProvider->getBySku($skuTwo);

        $this->assertEquals($productMockOne, $firstActualProduct);
        $this->assertEquals(null, $secondActualProduct);
    }
}
