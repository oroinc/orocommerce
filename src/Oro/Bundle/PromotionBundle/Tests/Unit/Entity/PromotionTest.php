<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionSchedule;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class PromotionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', 123, false],
            ['rule', new Rule(), false],
            ['owner', new User()],
            ['organization', new Organization()],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
            ['discountConfiguration', new DiscountConfiguration(), false],
            ['useCoupons', true],
            ['productsSegment', new Segment(), false],
        ];

        $this->assertPropertyAccessors(new Promotion(), $properties);
    }

    public function testCollections()
    {
        $collections = [
            ['labels', new LocalizedFallbackValue()],
            ['descriptions', new LocalizedFallbackValue()],
            ['scopes', new Scope()],
            ['schedules', new PromotionSchedule()],
            ['coupons', new Coupon()],
        ];

        $this->assertPropertyCollections(new Promotion(), $collections);
    }

    public function testResetScopes()
    {
        $promotion = new Promotion();
        $this->assertEmpty($promotion->getScopes());
        $promotion->addScope(new Scope());
        $this->assertNotEmpty($promotion->getScopes());
        $promotion->resetScopes();
        $this->assertEmpty($promotion->getScopes());
    }
}
