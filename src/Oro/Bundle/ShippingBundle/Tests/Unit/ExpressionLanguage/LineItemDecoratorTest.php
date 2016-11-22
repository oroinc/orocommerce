<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\EventListenerExpressionLanguage;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\ExpressionLanguage\LineItemDecorator;
use Oro\Bundle\ShippingBundle\ExpressionLanguage\LineItemDecoratorFactory;
use Oro\Component\Testing\Unit\EntityTrait;

class LineItemDecoratorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var LineItemDecoratorFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $factory;

    protected function setUp()
    {
        $this->factory = $this->getMockBuilder(LineItemDecoratorFactory::class)
            ->disableOriginalConstructor()->getMock();
    }

    public function testAccessors()
    {
        $product = $this->getEntity(Product::class, [
            'id' => 1
        ]);
        $lineItem = new ShippingLineItem();
        $lineItem->setProduct($product);

        $this->factory->expects(static::exactly(1))
            ->method('createProductDecorator')
            ->with([$lineItem], $product)
            ->willReturn($product);

        $decorator = new LineItemDecorator($this->factory, [$lineItem], $lineItem);
        $this->assertSame($product, $decorator->getProduct());
    }
}
