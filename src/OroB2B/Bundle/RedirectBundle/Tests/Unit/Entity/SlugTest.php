<?php

namespace OroB2B\Bundle\RedirectBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\RedirectBundle\Entity\Slug;

class RedirectDefaultValueTest extends EntityTestCase
{
    public function testProperties()
    {
        $properties = [
            ['id', 1],
            ['url', 'test/page'],
            ['routeName', 'page/1'],
            ['routeParameters', '[]'],
        ];

        $this->assertPropertyAccessors(new Slug(), $properties);
    }
}
