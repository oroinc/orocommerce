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
        $menuItemId = $menuItem->getId();
        $this->assertExecuteOperation(
            'DELETE',
            $menuItemId,
            $this->getContainer()->getParameter('orob2b_menu.entity.menu_item.class')
        );

        $this->assertEquals(
            [
                'success' => true,
                'message' => '',
                'messages' => [],
                'redirectUrl' => $this->getUrl('orob2b_menu_item_roots')
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }
}
