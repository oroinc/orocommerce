<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\ShippingMethodDiffMapper;
use Oro\Bundle\CurrencyBundle\Entity\Price;

class ShippingMethodDiffMapperTest extends AbstractCheckoutDiffMapperTest
{
    /**
     * @var CheckoutShippingMethodsProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $checkoutShippingMethodsProviderMock;

    protected function setUp(): void
    {
        $this->checkoutShippingMethodsProviderMock = $this
            ->getMockBuilder(CheckoutShippingMethodsProviderInterface::class)
            ->getMock();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->checkoutShippingMethodsProviderMock);
    }

    public function testGetName()
    {
        $this->assertEquals('shipping_method', $this->mapper->getName());
    }

    public function testGetCurrentStateWOSM()
    {
        $this->checkout->setShippingMethod(null);
        $this->assertEquals('', $this->mapper->getCurrentState($this->checkout));
    }

    public function testIsStatesEqualWithEmptyShippingMethod()
    {
        $this->checkout->setShippingMethod('flat_rate');
        $this->assertTrue($this->mapper->isStatesEqual($this->checkout, [], []));
    }

    public function testStateWithoutShippingMethod()
    {
        $this->checkout->setShippingMethod(null);
        $this->assertTrue($this->mapper->isStatesEqual($this->checkout, [], []));
    }

    /**
     * @dataProvider evaluateProvider
     * @param string $methodPrice
     * @param string $methodName
     * @param string $typeName
     * @param bool $expected
     */
    public function testGetCurrentState($methodPrice, $methodName, $typeName, $expected)
    {
        $this->checkoutShippingMethodsProviderMock
            ->expects($this->once())
            ->method('getPrice')
            ->willReturn($methodPrice);

        $this->checkout->setShippingMethod($methodName)->setShippingMethodType($typeName);
        static::assertEquals($expected, $this->mapper->getCurrentState($this->checkout));
    }

    /**
     * @return array
     */
    public function evaluateProvider()
    {
        return [
            'wrong_method'                    => [
                'methodPrice' => null,
                'method'      => 'flat_rate',
                'type'        => 'per_order',
                'expected'    => '',
            ],
            'correct_method_correct_type'     => [
                'methodPrice' => Price::create(10, 'USD'),
                'method'      => 'flat_rate',
                'type'        => 'per_order',
                'expected'    => md5(serialize([
                    'method' => 'flat_rate',
                    'type'   => 'per_order',
                    'price'  => Price::create(10, 'USD'),
                ])),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getMapper()
    {
        return new ShippingMethodDiffMapper($this->checkoutShippingMethodsProviderMock);
    }
}
