<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend\Api\Rest;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListConfigurableLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class LineItemControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                LoadProductData::class,
                LoadProductUnits::class,
                LoadShoppingLists::class,
                LoadShoppingListLineItems::class,
                LoadShoppingListConfigurableLineItems::class,
            ]
        );
    }

    public function testDelete(): void
    {
        /** @var LineItem $lineItem */
        $lineItem = $this->getReference('shopping_list_configurable_line_item.4');

        $this->client->jsonRequest(
            'DELETE',
            $this->getUrl('oro_api_shopping_list_frontend_delete_line_item', ['id' => $lineItem->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $secondItem = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(LineItem::class)
            ->getRepository(LineItem::class)
            ->findOneBy(['id' => $this->getReference('shopping_list_configurable_line_item.5')->getId()]);

        $this->assertTrue($secondItem === null);
    }

    public function testDeleteOnlyCurrent(): void
    {
        /** @var LineItem $lineItem */
        $lineItem = $this->getReference('shopping_list_configurable_line_item.4');

        $this->client->jsonRequest(
            'DELETE',
            $this->getUrl(
                'oro_api_shopping_list_frontend_delete_line_item',
                ['id' => $lineItem->getId(), 'onlyCurrent' => 1]
            )
        );

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $secondItem = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(LineItem::class)
            ->getRepository(LineItem::class)
            ->findOneBy(['id' => $this->getReference('shopping_list_configurable_line_item.5')->getId()]);

        $this->assertNotNull($secondItem);
    }

    public function testDeleteConfigurable(): void
    {
        /** @var LineItem $lineItem */
        $lineItem = $this->getReference('shopping_list_configurable_line_item.4');

        $this->client->jsonRequest(
            'DELETE',
            $this->getUrl(
                'oro_api_shopping_list_frontend_delete_line_item_configurable',
                [
                    'shoppingListId' => $lineItem->getShoppingList()->getId(),
                    'productId' => $lineItem->getParentProduct()->getId(),
                    'unitCode' => $lineItem->getProductUnitCode(),
                ]
            )
        );

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $secondItem = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(LineItem::class)
            ->getRepository(LineItem::class)
            ->findOneBy(['id' => $this->getReference('shopping_list_configurable_line_item.5')->getId()]);

        $this->assertNull($secondItem);
    }

    public function testDeleteWhenNoLineItem(): void
    {
        $this->client->jsonRequest(
            'DELETE',
            $this->getUrl('oro_api_shopping_list_frontend_delete_line_item', ['id' => 99999999])
        );

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 404);
    }
}
