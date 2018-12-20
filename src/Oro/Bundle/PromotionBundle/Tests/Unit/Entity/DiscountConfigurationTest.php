<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Entity;

use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class DiscountConfigurationTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', 123, false],
            ['type', 'some type', false],
            ['options', ['some option']],
        ];

        $this->assertPropertyAccessors(new DiscountConfiguration(), $properties);
    }
}
