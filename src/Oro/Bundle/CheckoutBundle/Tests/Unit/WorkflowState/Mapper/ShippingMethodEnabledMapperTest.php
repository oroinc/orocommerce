<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory;
use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\ShippingMethodEnabledMapper;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;

class ShippingMethodEnabledMapperTest extends AbstractCheckoutDiffMapperTest
{
    /**
     * @var ShippingPriceProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingPriceProvider;

    /**
     * @var ShippingContextProviderFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingContextProviderFactory;

    protected function setUp()
    {
        $this->shippingPriceProvider = $this->getMockBuilder(ShippingPriceProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingContextProviderFactory = $this->getMockBuilder(ShippingContextProviderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();

        unset($this->shippingPriceProvider, $this->shippingContextProviderFactory);
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

        $shippingContext = new ShippingContext();
        $this->shippingContextProviderFactory->expects(static::any())
            ->method('create')
            ->willReturn($shippingContext);
        $this->shippingPriceProvider->expects(static::any())
            ->method('getPrice')
            ->willReturn($methodPrice);

        $this->checkout->setShippingMethod($methodName)->setShippingMethodType($typeName);

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
        return new ShippingMethodEnabledMapper($this->shippingPriceProvider, $this->shippingContextProviderFactory);
    }
}
