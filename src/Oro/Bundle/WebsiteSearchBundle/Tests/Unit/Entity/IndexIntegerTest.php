<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Entity;

use Oro\Bundle\WebsiteSearchBundle\Entity\IndexInteger;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class IndexIntegerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', 1],
            ['item', new Item()],
            ['field', 'some_field'],
            ['value', 666],
        ];
        $this->assertPropertyAccessors(new IndexInteger(), $properties);
    }
}
