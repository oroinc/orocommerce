<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Layout\LayoutContext;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\CheckoutBundle\Layout\DataProvider\TotalsDataProvider;
use OroB2B\Bundle\CheckoutBundle\Provider\CheckoutTotalsProvider;

class TotalsDataProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var CheckoutTotalsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutTotalsProvider;

    /**
     * @var TotalsDataProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->checkoutTotalsProvider = $this->getMockBuilder(CheckoutTotalsProvider::class)
            ->disableOriginalConstructor()->getMock();

        $this->provider = new TotalsDataProvider($this->checkoutTotalsProvider);
    }

    public function testGetData()
    {
        $checkout = $this->getEntity('OroB2B\Bundle\CheckoutBundle\Entity\Checkout', ['id' => 42]);

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

        $context = new LayoutContext();
        $context->data()->set('checkout', null, $checkout);

        $result = $this->provider->getData($context);
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
