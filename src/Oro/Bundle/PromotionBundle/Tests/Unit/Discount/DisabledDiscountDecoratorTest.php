<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PromotionBundle\Discount\DisabledDiscountContextDecorator;
use Oro\Bundle\PromotionBundle\Discount\DisabledDiscountDecorator;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;

class DisabledDiscountDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DiscountInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $discount;

    /**
     * @var DisabledDiscountDecorator
     */
    protected $disabledDiscountDecorator;

    protected function setUp(): void
    {
        $this->discount = $this->createMock(DiscountInterface::class);
        $this->disabledDiscountDecorator = new DisabledDiscountDecorator($this->discount);
    }

    public function testConfigure()
    {
        $options = ['some' => 'option'];
        $resolvedOptions = ['some' => 'thig'];

        $this->discount
            ->expects($this->once())
            ->method('configure')
            ->with($options)
            ->willReturn($resolvedOptions);

        $this->assertEquals($resolvedOptions, $this->disabledDiscountDecorator->configure($options));
    }

    public function testGetMatchingProducts()
    {
        $matchingProducts = [new Product(), new Product()];
        $this->discount
            ->expects($this->once())
            ->method('getMatchingProducts')
            ->willReturn($matchingProducts);

        $this->assertEquals($matchingProducts, $this->disabledDiscountDecorator->getMatchingProducts());
    }

    public function testSetMatchingProducts()
    {
        $matchingProducts = [new Product(), new Product()];
        $this->discount
            ->expects($this->once())
            ->method('setMatchingProducts')
            ->with($matchingProducts);

        $this->disabledDiscountDecorator->setMatchingProducts($matchingProducts);
    }

    public function testGetDiscountType()
    {
        $discountType = DiscountInterface::TYPE_AMOUNT;
        $this->discount
            ->expects($this->once())
            ->method('getDiscountType')
            ->willReturn($discountType);

        $this->assertEquals($discountType, $this->disabledDiscountDecorator->getDiscountType());
    }

    public function testGetDiscountValue()
    {
        $discountValue = 77.0;
        $this->discount
            ->expects($this->once())
            ->method('getDiscountValue')
            ->willReturn($discountValue);

        $this->assertEquals($discountValue, $this->disabledDiscountDecorator->getDiscountValue());
    }

    public function testGetDiscountCurrency()
    {
        $currency = 'USD';
        $this->discount
            ->expects($this->once())
            ->method('getDiscountCurrency')
            ->willReturn($currency);

        $this->assertEquals($currency, $this->disabledDiscountDecorator->getDiscountCurrency());
    }

    public function testApply()
    {
        $discountContext = new DiscountContext();
        $this->discount
            ->expects($this->once())
            ->method('apply')
            ->with(new DisabledDiscountContextDecorator($discountContext));

        $this->disabledDiscountDecorator->apply($discountContext);
    }

    public function testCalculate()
    {
        $entity = new Order();
        $this->discount
            ->expects($this->never())
            ->method('calculate');

        $this->assertEquals(0.0, $this->disabledDiscountDecorator->calculate($entity));
    }

    public function testGetPromotion()
    {
        $promotion = new Promotion();
        $this->discount
            ->expects($this->once())
            ->method('getPromotion')
            ->willReturn($promotion);

        $this->assertSame($promotion, $this->disabledDiscountDecorator->getPromotion());
    }

    public function testSetPromotion()
    {
        $promotion = new Promotion();
        $this->discount
            ->expects($this->once())
            ->method('setPromotion')
            ->with($promotion);

        $this->disabledDiscountDecorator->setPromotion($promotion);
    }
}
