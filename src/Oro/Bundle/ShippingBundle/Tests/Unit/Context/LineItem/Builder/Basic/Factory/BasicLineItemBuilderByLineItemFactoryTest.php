<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context\LineItem\Builder\Basic\Factory;

use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Basic\BasicShippingLineItemBuilder;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Basic\Factory\BasicLineItemBuilderByLineItemFactory;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory\ShippingLineItemBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Tests\Unit\Context\AbstractShippingLineItemTest;
use PHPUnit\Framework\MockObject\MockObject;

class BasicLineItemBuilderByLineItemFactoryTest extends AbstractShippingLineItemTest
{
    private ShippingLineItemBuilderFactoryInterface|MockObject $lineItemBuilderFactory;
    private BasicLineItemBuilderByLineItemFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lineItemBuilderFactory = $this->createMock(ShippingLineItemBuilderFactoryInterface::class);

        $this->factory = new BasicLineItemBuilderByLineItemFactory($this->lineItemBuilderFactory);
    }

    public function testCreate()
    {
        $lineItem = new ShippingLineItem($this->getShippingLineItemParams());

        $builder = new BasicShippingLineItemBuilder(
            $lineItem->getProductUnit(),
            $lineItem->getProductUnitCode(),
            $lineItem->getQuantity(),
            $lineItem->getProductHolder()
        );

        $this->lineItemBuilderFactory->expects(self::any())
            ->method('createBuilder')
            ->willReturn($builder);

        $builder = $this->factory->createBuilder($lineItem);

        $this->assertEquals($lineItem, $builder->getResult());
    }
}
