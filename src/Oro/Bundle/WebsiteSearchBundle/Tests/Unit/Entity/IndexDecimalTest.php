<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Entity;

use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDecimal;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class IndexDecimalTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', 1],
            ['item', new Item()],
            ['field', 'some_field'],
            ['value', 3.14],
        ];
        $this->assertPropertyAccessors(new IndexDecimal(), $properties);
    }
}
