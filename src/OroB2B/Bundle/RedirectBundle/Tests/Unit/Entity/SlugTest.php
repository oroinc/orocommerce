<?php

namespace OroB2B\Bundle\RedirectBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\RedirectBundle\Entity\Slug;

class SlugTest extends EntityTestCase
{
    public function testProperties()
    {
        $properties = [
            ['id', 1],
            ['url', 'test/page'],
            ['routeName', 'orob2b_cms_page_view'],
            ['routeParameters', ['id' => 1]],
        ];

        $this->assertPropertyAccessors(new Slug(), $properties);
    }
}
