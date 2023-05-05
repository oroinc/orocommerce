<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context\LineItem\Builder\Basic\Factory;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Basic\BasicShippingLineItemBuilder;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Basic\Factory\BasicShippingLineItemBuilderFactory;

class BasicShippingLineItemBuilderFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $unitCode = 'someCode';
        $quantity = 15;

        $productUnit = $this->createMock(ProductUnit::class);
        $productHolder = $this->createMock(ProductHolderInterface::class);

        $builderFactory = new BasicShippingLineItemBuilderFactory();

        $builder = $builderFactory->createBuilder(
            $productUnit,
            $unitCode,
            $quantity,
            $productHolder
        );

        $expectedBuilder = new BasicShippingLineItemBuilder(
            $productUnit,
            $unitCode,
            $quantity,
            $productHolder
        );

        $this->assertEquals($expectedBuilder, $builder);
    }
}
