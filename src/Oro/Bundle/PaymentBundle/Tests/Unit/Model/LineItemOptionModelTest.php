<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Model;

use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class LineItemOptionModelTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['name', 'Name'],
            ['description', 'Description'],
            ['cost', 5.23],
            ['qty', 2.0],
            ['currency', 'USD'],
            ['unit', 'kg'],
        ];

        $this->assertPropertyAccessors(new LineItemOptionModel(), $properties);
    }
}
