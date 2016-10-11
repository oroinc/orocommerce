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
        $this->totalProcessorProvider = $this->getMockBuilder(TotalProcessorProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();

        unset($this->totalProcessorProvider);
    }

    public function testGetName()
    {
        $this->assertEquals('total_amount', $this->mapper->getName());
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
            'amount' => 1234,
            'currency' => 'EUR',
        ];

        $state2 = [
            'amount' => 1234,
            'currency' => 'EUR',
        ];

        $this->assertTrue($this->mapper->isStatesEqual($this->checkout, $state1, $state2));
    }

    /**
     * @dataProvider isStatesEqualFalseProvider
     * @param array $state1
     * @param array $state2
     */
    public function testIsStatesEqualFalse($state1, $state2)
    {
        $this->assertFalse($this->mapper->isStatesEqual($this->checkout, $state1, $state2));
    }

    /**
     * @return array
     */
    public function isStatesEqualFalseProvider()
    {
        return [
            'with different currency and amount' => [
                'state1' => [
                    'amount' => 1000,
                    'currency' => 'EUR',
                ],
                'state2' => [
                    'amount' => 2000,
                    'currency' => 'USD',
                ],
            ],
            'with different currency' => [
                'state1' => [
                    'amount' => 1000,
                    'currency' => 'EUR',
                ],
                'state2' => [
                    'amount' => 1000,
                    'currency' => 'USD',
                ],
            ],
            'with different amount' => [
                'state1' => [
                    'amount' => 1000,
                    'currency' => 'EUR',
                ],
                'state2' => [
                    'amount' => 2000,
                    'currency' => 'EUR',
                ],
            ],
            'state1 without amount' => [
                'state1' => [
                    'currency' => 'EUR',
                ],
                'state2' => [
                    'amount' => 2000,
                    'currency' => 'EUR',
                ],
            ],
            'state2 without amount' => [
                'state1' => [
                    'amount' => 2000,
                    'currency' => 'EUR',
                ],
                'state2' => [
                    'currency' => 'EUR',
                ],
            ],
            'state1 without currency' => [
                'state1' => [
                    'amount' => 2000,
                ],
                'state2' => [
                    'amount' => 2000,
                    'currency' => 'EUR',
                ],
            ],
            'state2 without currency' => [
                'state1' => [
                    'amount' => 2000,
                    'currency' => 'EUR',
                ],
                'state2' => [
                    'amount' => 2000,
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getMapper()
    {
        return new TotalAmountDiffMapper($this->totalProcessorProvider);
    }
}
