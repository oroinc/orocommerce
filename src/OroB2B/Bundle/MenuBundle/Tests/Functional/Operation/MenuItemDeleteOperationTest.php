<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;

use OroB2B\Bundle\MenuBundle\Entity\MenuItem;

/**
 * @dbIsolation
 */
class MenuItemDeleteOperationTest extends ActionTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\MenuBundle\Tests\Functional\DataFixtures\LoadMenuItemData'
            ]
        );
    }

    public function testDelete()
    {
        /** @var MenuItem $menuItem */
        $menuItem = $this->getReference('menu_item.1');

        $this->assertDeleteOperation(
            $menuItem->getId(),
            'orob2b_menu.entity.menu_item.class',
            'orob2b_menu_item_roots'
        );
    }
}
