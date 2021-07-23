<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Collection;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShippingBundle\Collection\ProductShippingOptionsGroupedByProductAndUnitCollection;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use PHPUnit\Framework\TestCase;

class ProductShippingOptionsGroupedByProductAndUnitCollectionTest extends TestCase
{
    public function testGet()
    {
        $collection = new ProductShippingOptionsGroupedByProductAndUnitCollection();
        $options = [
            $this->createShippingOption(1, 'each'),
            $this->createShippingOption(1, 'set'),
            $this->createShippingOption(2, 'set'),
        ];

        $collection
            ->add($options[0])
            ->add($options[1])
            ->add($options[2]);

        static::assertNull($collection->get(3, 'each'));
        static::assertNull($collection->get(2, 'box'));
        static::assertSame($options[0], $collection->get(1, 'each'));
        static::assertSame($options[2], $collection->get(2, 'set'));
    }

    private function createShippingOption(int $productId, string $unitCode): ProductShippingOptions
    {
        $option = new ProductShippingOptions();

        $option
            ->setProduct($this->createProduct($productId))
            ->setProductUnit($this->createProductUnit($unitCode));

        return $option;
    }

    private function createProductUnit(string $code): ProductUnit
    {
        return (new ProductUnit())->setCode($code);
    }

    /**
     * @param int $id
     *
     * @return Product|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createProduct(int $id)
    {
        $product = $this->createMock(Product::class);
        $product
            ->expects(static::any())
            ->method('getId')
            ->willReturn($id);

        return $product;
    }
}
