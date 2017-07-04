<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity;

use Oro\Bundle\OrderBundle\Entity\Order;
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
            ['order', new Order(), false],
            ['promotion', new Promotion(), false],
            ['promotionName', 'New Promotion', false],
            ['configOptions', [1, 2, 3]],
            ['options', [3, 2, 1]],
        ];

        $this->assertPropertyAccessors(new AppliedDiscount(), $properties);
    }
}
