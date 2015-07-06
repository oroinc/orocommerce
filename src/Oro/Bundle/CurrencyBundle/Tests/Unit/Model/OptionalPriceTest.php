<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Model;

use Oro\Component\Testing\Unit\EntityTestCase;

use Oro\Bundle\CurrencyBundle\Model\OptionalPrice;

class OptionalPriceTest extends EntityTestCase
{
    public function testAccessors()
    {
        $properties = [
            ['value', 10],
            ['currency', 'USD'],
        ];

        static::assertPropertyAccessors(new OptionalPrice(), $properties);
    }
}
