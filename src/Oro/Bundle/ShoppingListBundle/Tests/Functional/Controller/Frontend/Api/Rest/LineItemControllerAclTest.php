<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserACLData;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListACLData;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class LineItemControllerAclTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(
                LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_LOCAL
            )
        );

        $this->loadFixtures([
            LoadShoppingListACLData::class,
            LoadShoppingListLineItems::class,
        ]);
    }

    public function testDelete()
    {
        /* @var $lineItem LineItem */
        $lineItem = $this->getReference('shopping_list_line_item.1');

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_shopping_list_frontend_delete_line_item', ['id' => $lineItem->getId()])
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_NO_CONTENT);
    }
}
