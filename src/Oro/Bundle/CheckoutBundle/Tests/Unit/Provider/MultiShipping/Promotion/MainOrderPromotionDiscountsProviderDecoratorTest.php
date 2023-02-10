<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider\MultiShipping\Promotion;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\Promotion\MainOrderPromotionDiscountsProviderDecorator;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Provider\PromotionDiscountsProviderInterface;

class MainOrderPromotionDiscountsProviderDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var PromotionDiscountsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $baseDiscountsProvider;

    /** @var MainOrderPromotionDiscountsProviderDecorator */
    private $discountProvider;

    protected function setUp(): void
    {
        $this->baseDiscountsProvider = $this->createMock(PromotionDiscountsProviderInterface::class);

        $this->discountProvider = new MainOrderPromotionDiscountsProviderDecorator($this->baseDiscountsProvider);
    }

    public function testGetDiscounts()
    {
        $mainOrder = new Order();
        $subOrder1 = new Order();
        $subOrder2 = new Order();

        $mainOrder->addSubOrder($subOrder1);
        $mainOrder->addSubOrder($subOrder2);

        $this->baseDiscountsProvider->expects($this->never())
            ->method('getDiscounts');

        $context = $this->createMock(DiscountContextInterface::class);

        $discounts = $this->discountProvider->getDiscounts($mainOrder, $context);

        $this->assertIsArray($discounts);
        $this->assertEmpty($discounts);
    }

    /**
     * @dataProvider getTestGetDiscountsWithoutSubOrdersData
     */
    public function testGetDiscountsWithoutSubOrders(object $entity)
    {
        $this->baseDiscountsProvider->expects($this->once())
            ->method('getDiscounts');

        $context = $this->createMock(DiscountContextInterface::class);
        $discounts = $this->discountProvider->getDiscounts($entity, $context);

        $this->assertIsArray($discounts);
        $this->assertEmpty($discounts);
    }

    public function getTestGetDiscountsWithoutSubOrdersData(): array
    {
        return [
            'Entity is not Order' => [
                'entity' => new Checkout(),
            ],
            'Entity is Order without subOrders' => [
                'entity' => new Order()
            ]
        ];
    }
}
