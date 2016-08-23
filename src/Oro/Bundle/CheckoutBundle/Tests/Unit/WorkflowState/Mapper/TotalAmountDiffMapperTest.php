<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\TotalAmountDiffMapper;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class TotalAmountDiffMapperTest extends AbstractCheckoutDiffMapperTest
{
    /**
     * @var TotalProcessorProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $totalProcessorProvider;

    protected function setUp()
    {
        parent::setUp();

        $this->totalProcessorProvider = $this->getMockBuilder(
            'Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->mapper = new TotalAmountDiffMapper($this->totalProcessorProvider);
    }

    protected function tearDown()
    {
        parent::tearDown();

        unset($this->totalProcessorProvider);
    }

    public function testGetName()
    {
        $this->assertEquals('totalAmount', $this->mapper->getName());
    }

    public function testGetCurrentState()
    {
        $total = new Subtotal();
        $total->setAmount(1264);
        $total->setCurrency('EUR');

        $this->totalProcessorProvider
            ->expects($this->once())
            ->method('getTotal')
            ->with($this->checkout)
            ->willReturn($total);

        $result = $this->mapper->getCurrentState($this->checkout);

        $this->assertEquals(
            [
                'amount' => 1264,
                'currency' => 'EUR',
            ],
            $result
        );
    }

    public function testIsStatesEqualTrue()
    {
        $state1 = [
            'parameter1' => 10,
            'totalAmount' => [
                'amount' => 1234,
                'currency' => 'EUR'
            ],
            'parameter3' => 'green',
        ];

        $state2 = [
            'parameter1' => 10,
            'totalAmount' => [
                'amount' => 1234,
                'currency' => 'EUR'
            ],
            'parameter3' => 'green',
        ];

        $entity = new \stdClass();

        $this->assertEquals(true, $this->mapper->isStatesEqual($entity, $state1, $state2));
    }

    /**
     * @dataProvider isStatesEqualFalseProvider
     * @param array $state1
     * @param array $state2
     */
    public function testIsStatesEqualFalse($state1, $state2)
    {
        $entity = new \stdClass();

        $this->assertEquals(false, $this->mapper->isStatesEqual($entity, $state1, $state2));
    }

    /**
     * @return array
     */
    public function isStatesEqualFalseProvider()
    {
        return [
            'with different currency and amount' => [
                'state1' => [
                    'parameter1' => 10,
                    'totalAmount' => [
                        'amount' => 1234,
                        'currency' => 'EUR'
                    ],
                    'parameter3' => 'green'
                ],
                'state2' => [
                    'parameter1' => 10,
                    'totalAmount' => [
                        'amount' => 12,
                        'currency' => 'USD'
                    ],
                    'parameter3' => 'green'
                ]
            ],
            'with different currency' => [
                'state1' => [
                    'parameter1' => 10,
                    'totalAmount' => [
                        'amount' => 1234,
                        'currency' => 'EUR'
                    ],
                    'parameter3' => 'green'
                ],
                'state2' => [
                    'parameter1' => 10,
                    'totalAmount' => [
                        'amount' => 1234,
                        'currency' => 'USD'
                    ],
                    'parameter3' => 'green'
                ]
            ],
            'with different mount' => [
                'state1' => [
                    'parameter1' => 10,
                    'totalAmount' => [
                        'amount' => 1234,
                        'currency' => 'EUR'
                    ],
                    'parameter3' => 'green'
                ],
                'state2' => [
                    'parameter1' => 10,
                    'totalAmount' => [
                        'amount' => 11,
                        'currency' => 'EUR'
                    ],
                    'parameter3' => 'green'
                ]
            ]
        ];
    }

    public function testIsStatesEqualParameterNotExistInState1()
    {
        $state1 = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $state2 = [
            'parameter1' => 10,
            'totalAmount' => [
                'amount' => 1234,
                'currency' => 'EUR'
            ],
            'parameter3' => 'green',
        ];

        $entity = new \stdClass();

        $this->assertEquals(true, $this->mapper->isStatesEqual($entity, $state1, $state2));
    }

    public function testIsStatesEqualParameterNotExistInState2()
    {
        $state1 = [
            'parameter1' => 10,
            'parameter3' => 'green',
            'totalAmount' => [
                'amount' => 1234,
                'currency' => 'EUR'
            ]
        ];

        $state2 = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $entity = new \stdClass();

        $this->assertEquals(true, $this->mapper->isStatesEqual($entity, $state1, $state2));
    }
}
