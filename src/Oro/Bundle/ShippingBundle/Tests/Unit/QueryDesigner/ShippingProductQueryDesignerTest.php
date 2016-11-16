<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\QueryDesigner;

use Oro\Bundle\ShippingBundle\QueryDesigner\ShippingProductQueryDesigner;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ShippingProductQueryDesignerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testMethods()
    {
        static::assertPropertyAccessors(new ShippingProductQueryDesigner(), [
            ['entity', 'string'],
            ['definition', 'string'],
        ]);
    }
}
