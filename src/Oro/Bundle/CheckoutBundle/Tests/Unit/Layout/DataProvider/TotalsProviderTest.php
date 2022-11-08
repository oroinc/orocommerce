<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TotalsProvider;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutTotalsProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class TotalsProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var CheckoutTotalsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutTotalsProvider;

    /** @var TotalsProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->checkoutTotalsProvider = $this->createMock(CheckoutTotalsProvider::class);

        $this->provider = new TotalsProvider($this->checkoutTotalsProvider);
    }

    public function testGetData()
    {
        $checkout = $this->getEntity(Checkout::class, ['id' => 42]);

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
