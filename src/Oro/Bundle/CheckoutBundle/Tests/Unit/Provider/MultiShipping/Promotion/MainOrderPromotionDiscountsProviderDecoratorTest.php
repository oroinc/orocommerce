<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider\MultiShipping\Promotion;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\Promotion\MainOrderPromotionDiscountsProviderDecorator;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Provider\PromotionDiscountsProviderInterface;

class MainOrderPromotionDiscountsProviderDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var PromotionDiscountsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $baseDiscountsProvider;

    /** @var MainOrderPromotionDiscountsProviderDecorator */
    private $discountsProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->baseDiscountsProvider = $this->createMock(PromotionDiscountsProviderInterface::class);

        $this->discountsProvider = new MainOrderPromotionDiscountsProviderDecorator($this->baseDiscountsProvider);
    }

    public function testGetDiscountsForOrderWithSubOrders(): void
    {
        $sourceEntity = new Order();
        $sourceEntity->addSubOrder(new Order());
        $context = $this->createMock(DiscountContextInterface::class);

        $this->baseDiscountsProvider->expects(self::never())
            ->method('getDiscounts');

        $this->assertSame([], $this->discountsProvider->getDiscounts($sourceEntity, $context));
    }

    public function testGetDiscountsForOrderWithoutSubOrders(): void
    {
        $sourceEntity = new Order();
        $context = $this->createMock(DiscountContextInterface::class);
        $discounts = [$this->createMock(DiscountInterface::class)];

        $this->baseDiscountsProvider->expects(self::once())
            ->method('getDiscounts')
            ->with(self::identicalTo($sourceEntity), self::identicalTo($context))
            ->willReturn($discounts);

        $this->assertSame($discounts, $this->discountsProvider->getDiscounts($sourceEntity, $context));
    }

    public function testGetDiscountsForNotOrder(): void
    {
        $sourceEntity = new Checkout();
        $context = $this->createMock(DiscountContextInterface::class);
        $discounts = [$this->createMock(DiscountInterface::class)];

        $this->baseDiscountsProvider->expects(self::once())
            ->method('getDiscounts')
            ->with(self::identicalTo($sourceEntity), self::identicalTo($context))
            ->willReturn($discounts);

        $this->assertSame($discounts, $this->discountsProvider->getDiscounts($sourceEntity, $context));
    }
}
