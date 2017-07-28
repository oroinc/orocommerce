<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\CouponGeneration\Options;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CouponGenerationOptions;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CouponGenerationOptionsTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['owner', new BusinessUnit(), false],
            ['couponQuantity', 1, false],
            ['usesPerCoupon', 1, false],
            ['usesPerUser', 2, false],
            ['expirationDate', new \DateTime('UTC'), false],
            ['codeLength', 12],
            ['codeType', CouponGenerationOptions::NUMERIC_CODE_TYPE],
            ['codePrefix', '%'],
            ['codeSuffix', '-'],
            ['dashesSequence', 4],
        ];

        $this->assertPropertyAccessors(new CouponGenerationOptions(), $properties);
    }
}
