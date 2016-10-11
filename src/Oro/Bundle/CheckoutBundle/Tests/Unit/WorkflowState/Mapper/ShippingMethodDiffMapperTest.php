<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory;
use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\ShippingMethodDiffMapper;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;

class ShippingMethodDiffMapperTest extends AbstractCheckoutDiffMapperTest
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
        $shippingContext = new ShippingContext();
        $this->shippingContextProviderFactory->expects(static::once())
            ->method('create')
            ->willReturn($shippingContext);
        $this->shippingPriceProvider->expects(static::once())
            ->method('getApplicableMethodsWithTypesData')
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
                'methodPrice' => [
                    'wrong_method' => [
                        'types' => [
                            'flat_rate' => [
                                'identifier' => 'per_order',
                            ]
                        ]
                    ]
                ],
                'method'      => 'flat_rate',
                'type'        => 'per_order',
                'expected'    => '',
            ],
            'not_types'                       => [
                'methodPrice' => [
                    'flat_rate' => [
                        'identifier' => 'flat_rate'
                    ]
                ],
                'method'      => 'flat_rule',
                'type'        => 'per_order',
                'expected'    => '',
            ],
            'correct_method_not_correct_type' => [
                'methodPrice' => [
                    'flat_rate' => [
                        'identifier' => 'flat_rate',
                        'types'      => [
                            'flat_rate' => [
                                'identifier' => 'per_order',
                            ]
                        ]
                    ]
                ],
                'method'      => 'flat_rate',
                'type'        => null,
                'expected'    => '',
            ],
            'correct_method_correct_type'     => [
                'methodPrice' => [
                    'flat_rate' => [
                        'identifier' => 'flat_rate',
                        'types'      => [
                            'flat_rate' => [
                                'identifier' => 'per_order',
                            ]
                        ]
                    ]
                ],
                'method'      => 'flat_rate',
                'type'        => 'per_order',
                'expected'    => 'be54bff27aa03116c829e823fe577b11',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getMapper()
    {
        return new ShippingMethodDiffMapper($this->shippingPriceProvider, $this->shippingContextProviderFactory);
    }
}
