<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

class AppliedDiscountTest extends TestCase
{
    use EntityTrait;
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            ['id', 123, false],
            ['amount', 123.456, false],
            ['currency', 'USD', false],
            ['appliedPromotion', new AppliedPromotion(), false],
            ['lineItem', new OrderLineItem(), false],
            ['draftSessionUuid', '8f091a9a-c0d7-4560-975a-d3b0090bcfbd'],
        ];

        $this->assertPropertyAccessors(new AppliedDiscount(), $properties);
    }
}
