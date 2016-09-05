<?php

namespace Oro\Bundle\FrontendNavigationBundle\Tests\Unit\Entity;

use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use Oro\Bundle\FrontendNavigationBundle\Entity\MenuUpdate;

class MenuUpdateTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', 42],
            ['key', 'page_wrapper'],
            ['parentId', 'page_container'],
            ['title', 'title'],
            ['uri', 'uri'],
            ['menu', 'main_menu'],
            ['ownershipType', MenuUpdate::OWNERSHIP_GLOBAL],
            ['ownerId', 3],
            ['isActive', true],
            ['priority', 1],
            ['image', 'image.jpg'],
            ['description', 'description'],
            ['condition', 'condition'],
            ['website', new Website()],
        ];

        $this->assertPropertyAccessors(new MenuUpdate(), $properties);
    }
}
