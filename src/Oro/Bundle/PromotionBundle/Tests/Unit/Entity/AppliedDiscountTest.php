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
            ['order', new Order(), false],
            ['promotion', new Promotion(), false],
            ['configOptions', [1, 2, 3], false],
            ['options', [1, 2, 3], false],
        ];

        $this->assertPropertyAccessors(new AppliedDiscount(), $properties);
    }
}
