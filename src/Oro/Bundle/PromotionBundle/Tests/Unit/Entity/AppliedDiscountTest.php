<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class AppliedDiscountTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', 123, false],
            ['amount', 123.456, false],
            ['currency', 'USD', false],
            ['appliedPromotion', new AppliedPromotion(), false],
            ['lineItem', new OrderLineItem(), false],
        ];

        $this->assertPropertyAccessors(new AppliedDiscount(), $properties);
    }
}
