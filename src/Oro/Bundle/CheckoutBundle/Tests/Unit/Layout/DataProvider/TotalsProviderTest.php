<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TotalsProvider;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutTotalsProvider;

class TotalsProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var CheckoutTotalsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutTotalsProvider;

    /**
     * @var TotalsProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->checkoutTotalsProvider = $this->getMockBuilder(CheckoutTotalsProvider::class)
            ->disableOriginalConstructor()->getMock();

        $this->provider = new TotalsProvider($this->checkoutTotalsProvider);
    }

    public function testGetData()
    {
        $checkout = $this->getEntity('Oro\Bundle\CheckoutBundle\Entity\Checkout', ['id' => 42]);

        $this->checkoutTotalsProvider->expects($this->once())
            ->method('getTotalsArray')
            ->with($checkout)
            ->willReturn([
                'total' => [
                    'type' => 'Total',
                    'label' => 'Total',
                    'amount' => 100,
                    'currency' => 'USD',
                    'visible' => true,
                    'data' => null
                ],
                'subtotals' => [
                    [
                        'type' => 'subtotal',
                        'label' => 'Shipping Cost',
                        'amount' => 100,
                        'currency' => 'USD',
                        'visible' => true,
                        'data' => null
                    ]
                ]
            ]);

        $result = $this->provider->getData($checkout);
        $this->assertEquals([
            'total' => [
                'type' => 'Total',
                'label' => 'Total',
                'amount' => 100,
                'currency' => 'USD',
                'visible' => true,
                'data' => null
            ],
            'subtotal' => [
                'type' => 'subtotal',
                'label' => 'Shipping Cost',
                'amount' => 100,
                'currency' => 'USD',
                'visible' => true,
                'data' => null
            ],
            'subtotals' => [
                [
                    'type' => 'subtotal',
                    'label' => 'Shipping Cost',
                    'amount' => 100,
                    'currency' => 'USD',
                    'visible' => true,
                    'data' => null
                ]
            ]
        ], $result);
    }
}
