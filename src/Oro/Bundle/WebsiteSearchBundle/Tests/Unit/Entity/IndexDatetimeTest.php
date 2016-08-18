<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Entity;

use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDatetime;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class IndexDatetimeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', 1],
            ['item', new Item()],
            ['field', 'some_field'],
            ['value', new \DateTime()],
        ];
        $this->assertPropertyAccessors(new IndexDatetime(), $properties);
    }
}
