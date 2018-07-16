<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity;

use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionSchedule;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class PromotionScheduleTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', 123, false],
            ['promotion', new Promotion(), false],
            ['activeAt', $now, false],
            ['deactivateAt', $now, false]
        ];

        $this->assertPropertyAccessors(new PromotionSchedule(), $properties);
    }
}
