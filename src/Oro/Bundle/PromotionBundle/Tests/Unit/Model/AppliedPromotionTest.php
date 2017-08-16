<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Model\AppliedPromotion;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class AppliedPromotionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', 10],
            ['rule', new Rule(), false],
            ['discountConfiguration', new DiscountConfiguration(), false],
            ['useCoupons', true],
            ['productsSegment', new Segment(), false],
        ];

        $this->assertPropertyAccessors(new AppliedPromotion(), $properties);
    }

    public function testScopesCollection()
    {
        $promotion = new AppliedPromotion();
        $scope = new Scope();
        $promotion->addScope($scope);
        $this->assertInstanceOf(ArrayCollection::class, $promotion->getScopes());
        $this->assertEquals([$scope], $promotion->getScopes()->toArray());
    }

    public function testCouponsCollection()
    {
        $promotion = new AppliedPromotion();
        $coupon = new Coupon();
        $promotion->addCoupon($coupon);
        $this->assertInstanceOf(ArrayCollection::class, $promotion->getCoupons());
        $this->assertEquals([$coupon], $promotion->getCoupons()->toArray());
    }
}
