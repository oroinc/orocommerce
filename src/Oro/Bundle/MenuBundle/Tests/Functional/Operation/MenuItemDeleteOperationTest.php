<?php

namespace Oro\Bundle\MenuBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;
use Oro\Bundle\MenuBundle\Entity\MenuItem;

/**
 * @dbIsolation
 */
class MenuItemDeleteOperationTest extends ActionTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                'Oro\Bundle\MenuBundle\Tests\Functional\DataFixtures\LoadMenuItemData'
            ]
        );
    }

    public function testDelete()
    {
        /** @var MenuItem $menuItem */
        $menuItem = $this->getReference('menu_item.1');

        $this->assertDeleteOperation(
            $menuItem->getId(),
            'oro_menu.entity.menu_item.class',
            'oro_menu_item_roots'
        );
    }
}
