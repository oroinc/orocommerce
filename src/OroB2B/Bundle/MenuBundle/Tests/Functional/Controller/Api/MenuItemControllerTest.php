<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\MenuBundle\Entity\MenuItem;

/**
 * @dbIsolation
 */
class MenuItemControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

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
        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_delete_menu_item', ['id' => $menuItemId])
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);
    }
}
