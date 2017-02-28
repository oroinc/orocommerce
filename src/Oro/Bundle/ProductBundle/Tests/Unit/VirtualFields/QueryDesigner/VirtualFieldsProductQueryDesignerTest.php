<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\VirtualFields\QueryDesigner;

use Oro\Bundle\ProductBundle\VirtualFields\QueryDesigner\VirtualFieldsProductQueryDesigner;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class VirtualFieldsProductQueryDesignerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testMethods()
    {
        static::assertPropertyAccessors(new VirtualFieldsProductQueryDesigner(), [
            ['entity', 'string'],
            ['definition', 'string'],
        ]);
    }
}
