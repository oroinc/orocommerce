<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Entity;

use Oro\Bundle\WebsiteSearchBundle\Entity\IndexText;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class IndexTextTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', 1],
            ['item', new Item()],
            ['field', 'some_field'],
            ['value', 'Some text is here'],
        ];
        $this->assertPropertyAccessors(new IndexText(), $properties);
    }
}
