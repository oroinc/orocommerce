<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Oro\Bundle\OrderBundle\Provider\SkuCachedProductProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;

class SkuCachedProductProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productRepository;

    /**
     * @var SkuCachedProductProvider
     */
    protected $testedProvider;

    public function setUp()
    {
        $this->productRepository = $this->createMock(ProductRepository::class);

        $this->testedProvider = new SkuCachedProductProvider($this->productRepository);
    }

    /**
     * @return Product|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createProductMock()
    {
        return $this->createMock(Product::class);
    }

    public function testSuccessfulCaching()
    {
        $skuOne = '1';
        $skuTwo = '2';
        $productMockOne = $this->createProductMock($skuOne);
        $productMockTwo = $this->createProductMock($skuTwo);

        $this->productRepository
            ->expects(static::once())
            ->method('findBy')
            ->with(['sku' => [$skuOne, $skuTwo]])
            ->willReturn([$productMockOne, $productMockTwo]);

        $productMockOne
            ->expects(static::once())
            ->method('getSku')
            ->willReturn($skuOne);

        $productMockTwo
            ->expects(static::once())
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

        $this->productRepository
            ->expects(static::exactly(2))
            ->method('findOneBySku')
            ->with($sku)
            ->willReturn($productMock);

        $actualProduct = $this->testedProvider->getBySku($sku);

        $this->assertEquals($productMock, $actualProduct);

        $actualProduct = $this->testedProvider->getBySku($sku);

        $this->assertEquals($productMock, $actualProduct);
    }

    public function testNoProductsInCache()
    {
        $skuOne = '1';
        $skuTwo = '2';

        $this->productRepository
            ->expects(static::once())
            ->method('findBy')
            ->with(['sku' => [$skuOne, $skuTwo]])
            ->willReturn([]);

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
        $productMockOne = $this->createProductMock($skuOne);

        $this->productRepository
            ->expects(static::once())
            ->method('findBy')
            ->with(['sku' => [$skuOne, $skuTwo]])
            ->willReturn([$productMockOne]);

        $productMockOne
            ->expects(static::once())
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
