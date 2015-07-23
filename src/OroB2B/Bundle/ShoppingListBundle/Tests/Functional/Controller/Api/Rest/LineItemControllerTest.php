<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;

/**
 * @dbIsolation
 */
class LineItemControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProducts',
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits',
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists',
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems',
            ]
        );
    }

    public function testDelete()
    {
        /** @var LineItem $lineItem */
        $lineItem = $this->getReference('shopping_list_line_item.1');

        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_shopping_list_delete_line_item', ['id' => $lineItem->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);
    }
}
