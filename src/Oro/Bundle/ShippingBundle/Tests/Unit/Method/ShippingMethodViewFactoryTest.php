<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewFactory;

class ShippingMethodViewFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingMethodProvider;

    /** @var ShippingMethodViewFactory */
    private $shippingMethodViewFactory;

    protected function setUp(): void
    {
        $this->shippingMethodProvider = $this->createMock(ShippingMethodProviderInterface::class);

        $this->shippingMethodViewFactory = new ShippingMethodViewFactory($this->shippingMethodProvider);
    }

    public function testCreateMethodView()
    {
        $methodId = 'someId';
        $isGrouped = true;
        $label = 'someLabel';
        $sortOrder = 5;

        $expected = [
            'identifier' => $methodId,
            'isGrouped' => $isGrouped,
            'label' => $label,
            'sortOrder' => $sortOrder,
        ];

        $actual = $this->shippingMethodViewFactory->createMethodView($methodId, $label, $isGrouped, $sortOrder);

        $this->assertEquals($expected, $actual);
    }

    public function testCreateMethodViewTest()
    {
        $methodId = 'someId';
        $label = 'someLabel';
        $sortOrder = 5;
        $price = Price::create(5, 'USD');

        $expected = [
            'identifier' => $methodId,
            'label' => $label,
            'sortOrder' => $sortOrder,
            'price' => $price,
        ];

        $actual = $this->shippingMethodViewFactory->createMethodTypeView($methodId, $label, $sortOrder, $price);

        $this->assertEquals($expected, $actual);
    }

    public function testCreateMethodViewByShippingMethod()
    {
        $methodId = 'someId';
        $isGrouped = true;
        $label = 'someLabel';
        $sortOrder = 5;

        $method = $this->createMock(ShippingMethodInterface::class);
        $method->expects($this->once())
            ->method('getLabel')
            ->willReturn($label);
        $method->expects($this->once())
            ->method('isGrouped')
            ->willReturn($isGrouped);
        $method->expects($this->once())
            ->method('getSortOrder')
            ->willReturn($sortOrder);

        $this->shippingMethodProvider->expects($this->once())
            ->method('getShippingMethod')
            ->with($methodId)
            ->willReturn($method);

        $expected = [
            'identifier' => $methodId,
            'isGrouped' => $isGrouped,
            'label' => $label,
            'sortOrder' => $sortOrder,
        ];

        $actual = $this->shippingMethodViewFactory->createMethodViewByShippingMethod($methodId);

        $this->assertEquals($expected, $actual);
    }

    public function createMethodViewByShippingMethodWithNullMethod()
    {
        $methodId = 'someMethodId';

        $this->shippingMethodProvider->expects($this->once())
            ->method('getShippingMethod')
            ->with($methodId)
            ->willReturn(null);

        $actual = $this->shippingMethodViewFactory->createMethodViewByShippingMethod($methodId);

        $this->assertEquals(null, $actual);
    }

    public function testCreateMethodTypeViewByShippingMethodAndPrice()
    {
        $methodId = 'someId';
        $methodTypeId = 'someMethodTypeId';
        $label = 'someLabel';
        $sortOrder = 5;
        $price = Price::create(5, 'USD');

        $methodType = $this->createMock(ShippingMethodTypeInterface::class);
        $methodType->expects($this->once())
            ->method('getLabel')
            ->willReturn($label);
        $methodType->expects($this->once())
            ->method('getSortOrder')
            ->willReturn($sortOrder);

        $method = $this->createMock(ShippingMethodInterface::class);
        $method->expects($this->once())
            ->method('getType')
            ->willReturn($methodType);

        $this->shippingMethodProvider->expects($this->once())
            ->method('getShippingMethod')
            ->with($methodId)
            ->willReturn($method);

        $expected = [
            'identifier' => $methodTypeId,
            'label' => $label,
            'sortOrder' => $sortOrder,
            'price' => $price,
        ];

        $actual = $this->shippingMethodViewFactory->createMethodTypeViewByShippingMethodAndPrice(
            $methodId,
            $methodTypeId,
            $price
        );

        $this->assertEquals($expected, $actual);
    }

    public function testCreateMethodTypeViewByShippingMethodAndPriceWithNullMethod()
    {
        $methodId = 'someMethodId';
        $methodTypeId = 'someMethodTypeId';
        $price = Price::create(5, 'USD');

        $this->shippingMethodProvider->expects($this->once())
            ->method('getShippingMethod')
            ->with($methodId)
            ->willReturn(null);

        $actual = $this->shippingMethodViewFactory->createMethodTypeViewByShippingMethodAndPrice(
            $methodId,
            $methodTypeId,
            $price
        );

        $this->assertEquals(null, $actual);
    }

    public function testCreateMethodTypeViewByShippingMethodAndPriceWithNullMethodType()
    {
        $methodId = 'someMethodId';
        $methodTypeId = 'someMethodTypeId';
        $price = Price::create(5, 'USD');

        $method = $this->createMock(ShippingMethodInterface::class);
        $method->expects($this->once())
            ->method('getType')
            ->willReturn(null);

        $this->shippingMethodProvider->expects($this->once())
            ->method('getShippingMethod')
            ->with($methodId)
            ->willReturn($method);

        $actual = $this->shippingMethodViewFactory->createMethodTypeViewByShippingMethodAndPrice(
            $methodId,
            $methodTypeId,
            $price
        );

        $this->assertEquals(null, $actual);
    }
}
