<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class AppliedDiscountTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', 123, false],
            ['type', 'test-type', false],
            ['amount', 123.456, false],
            ['currency', 'USD', false],
            ['promotion', new Promotion(), false],
            ['promotionName', 'test-promotion', false],
            ['configOptions', [1, 2, 3]],
            ['lineItem', new OrderLineItem(), false],
            ['enabled', false, true],
            ['couponCode', 'newCode', null],
        ];

        $this->assertPropertyAccessors(new AppliedDiscount(), $properties);
    }
}
