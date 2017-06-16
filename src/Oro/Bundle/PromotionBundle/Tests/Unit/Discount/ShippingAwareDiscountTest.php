<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Discount\ShippingAwareDiscount;
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class ShippingAwareDiscountTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DiscountInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingDiscount;

    /**
     * @var ShippingAwareDiscount
     */
    private $discount;

    protected function setUp()
    {
        $this->shippingDiscount = $this->createMock(DiscountInterface::class);
        $this->discount = $this->getMockBuilder(ShippingAwareDiscount::class)
            ->setConstructorArgs([$this->shippingDiscount])
            ->getMockForAbstractClass();
    }

    public function testApplyWithShippingDiscount()
    {
        $options = [
            ShippingAwareDiscount::SHIPPING_DISCOUNT => ShippingDiscount::APPLY_TO_ITEMS
        ];
        /** @var DiscountContext|\PHPUnit_Framework_MockObject_MockObject $discountContext */
        $discountContext = $this->createMock(DiscountContext::class);
        $discountContext->expects($this->once())
            ->method('addShippingDiscount')
            ->with($this->shippingDiscount);

        $this->discount->configure($options);
        $this->discount->apply($discountContext);
    }

    public function testApplyWithoutShippingDiscount()
    {
        $options = [
            ShippingAwareDiscount::SHIPPING_DISCOUNT => null
        ];
        /** @var DiscountContext|\PHPUnit_Framework_MockObject_MockObject $discountContext */
        $discountContext = $this->createMock(DiscountContext::class);
        $discountContext->expects($this->never())
            ->method('addShippingDiscount');

        $this->discount->configure($options);
        $this->discount->apply($discountContext);
    }

    public function testSetMatchingProducts()
    {
        $products = new ArrayCollection([$this->createMock(Product::class)]);
        $this->shippingDiscount->expects($this->once())
            ->method('setMatchingProducts')
            ->with($products);
        $this->discount->setMatchingProducts($products);
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     * @param array $options
     */
    public function testInvalidOptions(array $options)
    {
        $this->expectException(InvalidOptionsException::class);
        $this->discount->configure($options);
    }

    /**
     * @return array
     */
    public function invalidOptionsDataProvider(): array
    {
        return [
            'invalid SHIPPING_DISCOUNT type' => [
                [
                    ShippingAwareDiscount::SHIPPING_DISCOUNT => []
                ]
            ],
            'invalid SHIPPING_DISCOUNT value' => [
                [
                    ShippingAwareDiscount::SHIPPING_DISCOUNT => 'abc'
                ]
            ]
        ];
    }
}
