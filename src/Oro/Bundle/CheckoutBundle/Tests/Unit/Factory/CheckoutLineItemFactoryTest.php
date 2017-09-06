<?php

namespace Oro\Bundle\CheckoutBundle\Bundle\Tests\Unit\Factory;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutLineItemFactory;
use Oro\Bundle\CheckoutBundle\Model\CheckoutLineItemConverterInterface;
use Oro\Bundle\CheckoutBundle\Model\CheckoutLineItemConverterRegistry;

class CheckoutLineItemFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var CheckoutLineItemConverterRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var CheckoutLineItemFactory */
    protected $factory;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->registry = $this->createMock(CheckoutLineItemConverterRegistry::class);
        $this->factory = new CheckoutLineItemFactory($this->registry);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->registry, $this->factory);
    }

    public function testCreate()
    {
        $source = new \stdClass();
        $expectedData = [
            $this->createMock(CheckoutLineItem::class),
        ];
        $converter = $this->createMock(CheckoutLineItemConverterInterface::class);
        $converter
            ->expects($this->once())
            ->method('convert')
            ->with($source)
            ->willReturn($expectedData);

        $this->registry
            ->expects($this->once())
            ->method('getConverter')
            ->with($source)
            ->willReturn($converter);

        $this->assertSame($expectedData, $this->factory->create($source));
    }
}
