<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity;

use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

class AppliedPromotionTest extends TestCase
{
    use EntityTrait;
    use EntityTestCaseTrait;

    public function testProperties(): void
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
            ['draftSessionUuid', '8f091a9a-c0d7-4560-975a-d3b0090bcfbd'],
        ];

        $this->assertPropertyAccessors(new AppliedPromotion(), $properties);
    }

    public function testCollections(): void
    {
        $collections = [
            ['appliedDiscounts', new AppliedDiscount()],
        ];

        $this->assertPropertyCollections(new AppliedPromotion(), $collections);
    }
}
