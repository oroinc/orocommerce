<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity;

use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class AppliedPromotionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', 123],
            ['active', false],
            ['appliedCoupon', new AppliedCoupon()],
            ['type', 'some type'],
            ['sourcePromotionId', 3],
            ['promotionName', 'some name'],
            ['configOptions', ['some options']],
            ['promotionData', ['some promotion data']],
        ];

        $this->assertPropertyAccessors(new AppliedPromotion(), $properties);
    }

    public function testCollections()
    {
        $collections = [
            ['appliedDiscounts', new AppliedDiscount()],
        ];

        $this->assertPropertyCollections(new AppliedPromotion(), $collections);
    }
}
