<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider\MultiShipping\Total;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\SplitCheckoutProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\Total\PromotionSubtotalProviderDecorator;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PromotionBundle\Provider\SubtotalProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PromotionSubtotalProviderDecoratorTest extends TestCase
{
    private SubtotalProvider|MockObject $baseSubtotalProvider;
    private RoundingServiceInterface|MockObject $rounding;
    private SplitCheckoutProvider|MockObject $splitCheckoutProvider;
    private PromotionSubtotalProviderDecorator $subtotalProvider;

    protected function setUp(): void
    {
        $this->baseSubtotalProvider = $this->createMock(SubtotalProvider::class);
        $this->rounding = $this->createMock(RoundingServiceInterface::class);
        $this->splitCheckoutProvider = $this->createMock(SplitCheckoutProvider::class);

        $this->subtotalProvider = new PromotionSubtotalProviderDecorator(
            $this->baseSubtotalProvider,
            $this->rounding,
            $this->splitCheckoutProvider
        );
    }

    public function testGetOrderSubtotal()
    {
        $mainOrder = new Order();
        $subOrder1 = new Order();
        $subOrder2 = new Order();

        $mainOrder->addSubOrder($subOrder1);
        $mainOrder->addSubOrder($subOrder2);

        $subTotals1 = [
            SubtotalProvider::ORDER_DISCOUNT_SUBTOTAL =>
                $this->createSubtotal('discount', 10.00, 'Discount'),
            SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL =>
                $this->createSubtotal('shipping', 0.00, 'Shipping')
        ];

        $subTotals2 = [
            SubtotalProvider::ORDER_DISCOUNT_SUBTOTAL =>
                $this->createSubtotal('discount', 5.00, 'Discount'),
            SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL =>
                $this->createSubtotal('shipping', 0.00, 'Shipping')
        ];

        $this->baseSubtotalProvider->expects($this->exactly(2))
            ->method('getSubtotal')
            ->willReturnMap([
                [$subOrder1, $subTotals1],
                [$subOrder2, $subTotals2]
            ]);

        $this->rounding->expects($this->exactly(2))
            ->method('round')
            ->willReturnOnConsecutiveCalls(15.00, 0.00);

        $subtotals = $this->subtotalProvider->getSubtotal($mainOrder);

        $this->assertCount(2, $subtotals);
        $this->assertArrayHasKey(SubtotalProvider::ORDER_DISCOUNT_SUBTOTAL, $subtotals);
        $this->assertArrayHasKey(SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL, $subtotals);

        $discountTotal = $subtotals[SubtotalProvider::ORDER_DISCOUNT_SUBTOTAL];
        $this->assertEquals(15.00, $discountTotal->getAmount());
        $this->assertTrue($discountTotal->isVisible());

        $shippingTotal = $subtotals[SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL];
        $this->assertEquals(0.00, $shippingTotal->getAmount());
        $this->assertFalse($shippingTotal->isVisible());
    }

    public function testGetCheckoutSubtotal()
    {
        $checkout = new Checkout();
        $subCheckout1 = new Checkout();
        $subCheckout2 = new Checkout();

        $subTotals1 = [
            SubtotalProvider::ORDER_DISCOUNT_SUBTOTAL =>
                $this->createSubtotal('discount', 10.00, 'Discount'),
            SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL =>
                $this->createSubtotal('shipping', 0.00, 'Shipping')
        ];

        $subTotals2 = [
            SubtotalProvider::ORDER_DISCOUNT_SUBTOTAL =>
                $this->createSubtotal('discount', 5.00, 'Discount'),
            SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL =>
                $this->createSubtotal('shipping', 0.00, 'Shipping')
        ];

        $this->splitCheckoutProvider->expects($this->once())
            ->method('getSubCheckouts')
            ->willReturn([$subCheckout1, $subCheckout2]);

        $this->baseSubtotalProvider->expects($this->exactly(2))
            ->method('getSubtotal')
            ->willReturnMap([
                [$subCheckout1, $subTotals1],
                [$subCheckout2, $subTotals2]
            ]);

        $this->rounding->expects($this->exactly(2))
            ->method('round')
            ->willReturnOnConsecutiveCalls(15.00, 0.00);

        $subtotals = $this->subtotalProvider->getSubtotal($checkout);

        $this->assertCount(2, $subtotals);
        $this->assertArrayHasKey(SubtotalProvider::ORDER_DISCOUNT_SUBTOTAL, $subtotals);
        $this->assertArrayHasKey(SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL, $subtotals);

        $discountTotal = $subtotals[SubtotalProvider::ORDER_DISCOUNT_SUBTOTAL];
        $this->assertEquals(15.00, $discountTotal->getAmount());
        $this->assertTrue($discountTotal->isVisible());

        $shippingTotal = $subtotals[SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL];
        $this->assertEquals(0.00, $shippingTotal->getAmount());
        $this->assertFalse($shippingTotal->isVisible());
    }

    /**
     * @param object $entity
     * @dataProvider getTestGetSubtotalWithoutSubOrdersData
     */
    public function testGetSubtotalWithoutSubOrders($entity)
    {
        $baseSubtotals = [
            SubtotalProvider::ORDER_DISCOUNT_SUBTOTAL =>
                $this->createSubtotal('discount', 10.00, 'Discount'),
            SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL =>
                $this->createSubtotal('shipping', 0.00, 'Shipping')
        ];

        $this->baseSubtotalProvider->expects($this->once())
            ->method('getSubtotal')
            ->willReturn($baseSubtotals);

        $this->splitCheckoutProvider->expects($this->any())
            ->method('getSubCheckouts')
            ->willReturn([]);

        $subtotals = $this->subtotalProvider->getSubtotal($entity);

        $this->assertCount(2, $subtotals);
        $this->assertArrayHasKey(SubtotalProvider::ORDER_DISCOUNT_SUBTOTAL, $subtotals);
        $this->assertArrayHasKey(SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL, $subtotals);

        $discountTotal = $subtotals[SubtotalProvider::ORDER_DISCOUNT_SUBTOTAL];
        $this->assertEquals(10.00, $discountTotal->getAmount());
        $this->assertTrue($discountTotal->isVisible());

        $shippingTotal = $subtotals[SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL];
        $this->assertEquals(0.00, $shippingTotal->getAmount());
        $this->assertFalse($shippingTotal->isVisible());
    }

    public function getTestGetSubtotalWithoutSubOrdersData()
    {
        return [
            'Entity is not supported' => [
                'entity' => new \StdClass(),
            ],
            'Order without subOrders' => [
                'entity' => new Order()
            ],
            'Checkout without subCheckouts' => [
                'entity' => new Checkout()
            ]
        ];
    }

    private function createSubtotal($type, $amount, $label): Subtotal
    {
        $subtotal = new Subtotal();
        $subtotal->setLabel($label);
        $subtotal->setType($type);
        $subtotal->setVisible($amount > 0.0);
        $subtotal->setAmount($amount);
        $subtotal->setCurrency('USD');
        $subtotal->setOperation(Subtotal::OPERATION_SUBTRACTION);
        $subtotal->setSortOrder(0);

        return $subtotal;
    }
}
