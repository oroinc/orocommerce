<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\ShippingMethodEnabledMapper;
use Oro\Bundle\CurrencyBundle\Entity\Price;

class ShippingMethodEnabledMapperTest extends AbstractCheckoutDiffMapperTest
{
    /** @var CheckoutShippingMethodsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutShippingMethodsProvider;

    protected function setUp(): void
    {
        $this->checkoutShippingMethodsProvider = $this->createMock(CheckoutShippingMethodsProviderInterface::class);

        parent::setUp();
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
     */
    public function testIsStatesEqual(
        ?Price $methodPrice,
        string $methodName,
        string $typeName,
        bool $expected
    ) {
        $this->checkout->setShippingMethod($methodName)->setShippingMethodType($typeName);

        $this->checkoutShippingMethodsProvider->expects($this->once())
            ->method('getPrice')
            ->willReturn($methodPrice);

        self::assertEquals($expected, $this->mapper->isStatesEqual($this->checkout, '', ''));
    }

    public function evaluateProvider(): array
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
        return new ShippingMethodEnabledMapper($this->checkoutShippingMethodsProvider);
    }
}
