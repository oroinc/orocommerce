<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserACLData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListACLData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class AjaxLineItemControllerAclTest extends WebTestCase
{
    /** @var Product */
    protected $product;

    /** @var ShoppingList */
    protected $shoppingList;

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
            LoadProductData::class,
        ]);

        $this->product = $this->getReference(LoadProductData::PRODUCT_1);
        $this->shoppingList = $this->getReference(LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_LOCAL);
    }

    public function testAddProductFromView()
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_shopping_list_frontend_add_product',
                [
                    'productId' => $this->product->getId(),
                    'shoppingListId' => $this->shoppingList->getId(),
                ]
            )
        );

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
    }

    /**
     * @depends testAddProductFromView
     */
    public function testRemoveProductFromView()
    {
        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_shopping_list_frontend_remove_product',
                [
                    'productId' => $this->product->getId(),
                    'shoppingListId' => $this->shoppingList->getId(),
                ]
            )
        );

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
    }

    public function testAddProductsMassAction()
    {
        $this->markTestSkipped('Enable in BB-5144');

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_shopping_list_add_products_massaction',
                [
                    'gridName' => 'frontend-product-search-grid',
                    'actionName' => 'oro_shoppinglist_frontend_addlineitemlist' . $this->shoppingList->getId(),
                    'shoppingList' => $this->shoppingList->getId(),
                    'inset' => 1,
                    'values' => $this->getReference('product.1')->getId(),
                ]
            )
        );

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
    }

    public function testAddProductsToNewMassAction()
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_shopping_list_add_products_to_new_massaction',
                [
                    'gridName' => 'frontend-product-search-grid',
                    'actionName' => 'oro_shoppinglist_frontend_addlineitemnew',
                    '_widgetContainer' => 'dialog',
                    '_wid' => 'test-uuid',
                ]
            )
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
    }
}
