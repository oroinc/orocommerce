<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Helper\ProductGrouper;

use Oro\Bundle\ProductBundle\Helper\ProductGrouper\ArrayProductsGrouper;
use Oro\Bundle\ProductBundle\Helper\ProductGrouper\ProductRowsGrouper;
use Oro\Bundle\ProductBundle\Helper\ProductGrouper\ProductsGrouperFactory;
use Oro\Bundle\ProductBundle\Helper\ProductGrouper\QuickAddRowGrouper;
use Oro\Bundle\ProductBundle\Helper\ProductGrouper\UnknownGrouperException;

class ProductsGrouperFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductsGrouperFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->factory = new ProductsGrouperFactory();
    }

    public function testCreateProductsGrouperCreatesArrayProductGrouper()
    {
        $this->assertInstanceOf(
            ArrayProductsGrouper::class,
            $this->factory->createProductsGrouper(ProductsGrouperFactory::ARRAY_PRODUCTS)
        );
    }

    public function testCreateProductsGrouperCreatesProductRowsGrouper()
    {
        $this->assertInstanceOf(
            ProductRowsGrouper::class,
            $this->factory->createProductsGrouper(ProductsGrouperFactory::PRODUCT_ROW)
        );
    }

    public function testCreateProductsGrouperCreatesQuickAddRowGrouper()
    {
        $this->assertInstanceOf(
            QuickAddRowGrouper::class,
            $this->factory->createProductsGrouper(ProductsGrouperFactory::QUICK_ADD_ROW)
        );
    }

    public function testCreateProductsGrouperThrowExceptionForUnknownType()
    {
        $this->expectException(UnknownGrouperException::class);
        $this->expectExceptionMessage('There is no Products Grouper for "unknown type".');

        $this->factory->createProductsGrouper('unknown type');
    }
}
