<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\ShippingMethodEnabledMapper;
use Oro\Bundle\CurrencyBundle\Entity\Price;

class ShippingMethodEnabledMapperTest extends AbstractCheckoutDiffMapperTest
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
        $this->assertEquals('shipping_method_enabled', $this->mapper->getName());
    }

    public function testGetCurrentStateWOSM()
    {
        $this->checkout->setShippingMethod(null);
        $this->assertEquals('', $this->mapper->getCurrentState($this->checkout));
    }

    public function testStateWithoutShippingMethod()
    {
        $this->checkout->setShippingMethod(null);
        $this->assertTrue($this->mapper->isStatesEqual($this->checkout, [], []));
    }

    /**
     * @dataProvider evaluateProvider
     * @param array $methodPrice
     * @param string $methodName
     * @param string $typeName
     * @param bool $expected
     */
    public function testIsStatesEqual($methodPrice, $methodName, $typeName, $expected)
    {
        $this->checkout->setShippingMethod($methodName)->setShippingMethodType($typeName);

        $this->checkoutShippingMethodsProviderMock
            ->expects($this->once())
            ->method('getPrice')
            ->willReturn($methodPrice);

        static::assertEquals($expected, $this->mapper->isStatesEqual($this->checkout, '', ''));
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
                'expected'    => false,
            ],
            'correct_method_correct_type'     => [
                'methodPrice' => Price::create(10, 'USD'),
                'method'      => 'flat_rate',
                'type'        => 'per_order',
                'expected'    => true,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getMapper()
    {
        return new ShippingMethodEnabledMapper($this->checkoutShippingMethodsProviderMock);
    }
}
