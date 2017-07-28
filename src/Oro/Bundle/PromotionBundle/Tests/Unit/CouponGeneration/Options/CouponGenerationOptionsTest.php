<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\CouponGeneration\Options;

use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CouponGenerationOptions;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CouponGenerationOptionsTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new CouponGenerationOptions(), [
            ['couponQuantity', 42],
            ['promotion', new Promotion()],
            ['usesPerCoupon', 42],
            ['usesPerUser', 42],
            ['expirationDate', new \DateTime()],
            ['codeLength', 42],
            ['codeType', 'some string'],
            ['codePrefix', 'some string'],
            ['codeSuffix', 'some string'],
            ['dashesSequence', 42],
            ['owner', new BusinessUnit()]
        ]);
    }
}
