<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;

use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;

/**
 * @dbIsolation
 */
class LineItemDeleteOperationTest extends ActionTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
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

        $this->assertExecuteOperation(
            'DELETE',
            $lineItem->getId(),
            $this->getContainer()->getParameter('orob2b_shopping_list.entity.line_item.class'),
            ['datagrid' => 'shopping-list-line-items-grid']
        );

        $this->assertEquals(
            [
                'success' => true,
                'message' => '',
                'messages' => [],
                'refreshGrid' => [
                    'shopping-list-line-items-grid'
                ],
                'flashMessages' => [
                    'success' => ['Shopping List Line Item deleted']
                ]
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }
}
