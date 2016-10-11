<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Condition;

use Oro\Bundle\CheckoutBundle\Condition\ShippingMethodSupports;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class ShippingMethodSupportsTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const METHOD = 'Method';

    /** @var ShippingMethodSupports */
    protected $condition;

    /** @var ShippingPriceProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $shippingPriceProvider;

    /** @var ShippingContextProviderFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $shippingContextProviderFactory;

    protected function setUp()
    {
        $this->shippingPriceProvider = $this
            ->getMockBuilder(ShippingPriceProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingContextProviderFactory = $this
            ->getMockBuilder(ShippingContextProviderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->condition = new ShippingMethodSupports(
            $this->shippingPriceProvider,
            $this->shippingContextProviderFactory
        );
    }

    protected function tearDown()
    {
        unset(
            $this->condition,
            $this->shippingContextProviderFactory,
            $this->shippingPriceProvider
        );
    }

    public function testGetName()
    {
        static::assertEquals(ShippingMethodSupports::NAME, $this->condition->getName());
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Missing "entity" option
     */
    public function testInitializeInvalid()
    {
        static::assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize([])
        );
    }

    public function testInitialize()
    {
        static::assertInstanceOf(
            'Oro\Component\ConfigExpression\Condition\AbstractCondition',
            $this->condition->initialize([
                'entity' => new Checkout()
            ])
        );
    }

    /**
     * @dataProvider evaluateProvider
     * @param string $methodPrice
     * @param string $methodName
     * @param string $typeName
     * @param bool $expected
     */
    public function testEvaluate($methodPrice, $methodName, $typeName, $expected)
    {
        $shippingContext = new ShippingContext();
        $this->shippingContextProviderFactory->expects(static::once())
            ->method('create')
            ->willReturn($shippingContext);
        $this->shippingPriceProvider->expects(static::once())
            ->method('getApplicableMethodsWithTypesData')
            ->willReturn($methodPrice);

        $checkout = new Checkout();
        $checkout->setShippingMethod($methodName)->setShippingMethodType($typeName);
        $this->condition->initialize([
            'entity' => $checkout
        ]);
        static::assertEquals($expected, $this->condition->evaluate([]));
    }

    /**
     * @return array
     */
    public function evaluateProvider()
    {
        return [
            'wrong_method'                        => [
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
                'expected'    => false,
            ],
            'not_types'              => [
                'methodPrice' => [
                    'flat_rate' => [
                        'identifier' => 'flat_rate'
                    ]
                ],
                'method'      => 'flat_rule',
                'type'        => 'per_order',
                'expected'    => false,
            ],
            'correct_method_not_correct_type' => [
                'methodPrice' => [
                    'flat_rate' => [
                        'identifier' => 'flat_rate',
                        'types' => [
                            'flat_rate' => [
                                'identifier' => 'per_order',
                            ]
                        ]
                    ]
                ],
                'method'   => 'flat_rate',
                'type'     => null,
                'expected' => false,
            ],
            'correct_method_correct_type'     => [
                'methodPrice' => [
                    'flat_rate' => [
                        'identifier' => 'flat_rate',
                        'types' => [
                            'flat_rate' => [
                                'identifier' => 'per_order',
                            ]
                        ]
                    ]
                ],
                'method'   => 'flat_rate',
                'type'     => 'per_order',
                'expected' => true,
            ],
        ];
    }

    public function testToArray()
    {
        $stdClass = new \stdClass();
        $this->condition->initialize([
            'entity' => $stdClass
        ]);
        $result = $this->condition->toArray();

        $key = '@' . ShippingMethodSupports::NAME;

        static::assertInternalType('array', $result);
        static::assertArrayHasKey($key, $result);

        $resultSection = $result[$key];
        static::assertInternalType('array', $resultSection);
        static::assertArrayHasKey('parameters', $resultSection);
        static::assertContains($stdClass, $resultSection['parameters']);
    }

    public function testCompile()
    {
        $toStringStub = new ToStringStub();
        $options = ['entity' => $toStringStub];

        $this->condition->initialize($options);
        $result = $this->condition->compile('$factory');
        static::assertEquals(
            sprintf(
                '$factory->create(\'%s\', [%s])',
                ShippingMethodSupports::NAME,
                $toStringStub
            ),
            $result
        );
    }
}
