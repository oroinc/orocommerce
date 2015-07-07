<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolation
 */
class ShoppingListControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            array_merge(
                $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW),
                ['HTTP_X-CSRF-Header' => 1]
            )
        );

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists'
            ]
        );
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('orob2b_shopping_list_frontend_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testSetCurrent()
    {
        $this->markTestSkipped('Skipped because of bug in data audit. Test will be fixed in BB-748');

        /** @var ShoppingList $list */
        $list = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $this->assertFalse($list->isCurrent());
        $this->client->request(
            'GET',
            $this->getUrl('orob2b_shopping_list_frontend_set_current', ['id' => $list->getId()])
        );
        $response = $this->client->getResponse();
        $this->assertEquals(302, $response->getStatusCode());
        /** @var ShoppingList $updatedList */
        $updatedList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $this->assertTrue($updatedList->isCurrent());
        /** @var ShoppingList $oldCurrent */
        $oldCurrent = $this->getReference(LoadShoppingLists::SHOPPING_LIST_2);
        $this->assertFalse($oldCurrent->isCurrent());
    }
}
