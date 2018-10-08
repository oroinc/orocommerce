<?php

namespace Oro\Bundle\RuleBundle\Tests\Unit\Entity;

use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class RuleTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testProperties()
    {
        $now = new \DateTime();
        $properties = [
            ['id', '123'],
            ['name', 'Test Rule'],
            ['enabled', true],
            ['sortOrder', 10],
            ['stopProcessing', true],
            ['expression', 'Test Rule'],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
        ];

        $rule = new Rule();
        static::assertPropertyAccessors($rule, $properties);
    }
}
