<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Factory;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutLineItemsFactory;
use Oro\Bundle\CheckoutBundle\Model\CheckoutLineItemConverterInterface;
use Oro\Bundle\CheckoutBundle\Model\CheckoutLineItemConverterRegistry;

class CheckoutLineItemsFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutLineItemConverterRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var CheckoutLineItemsFactory */
    protected $factory;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->registry = $this->createMock(CheckoutLineItemConverterRegistry::class);
        $this->factory = new CheckoutLineItemsFactory($this->registry);
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
