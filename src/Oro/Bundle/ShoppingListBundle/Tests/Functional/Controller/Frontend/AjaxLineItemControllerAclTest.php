<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserACLData;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListACLData;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItemUserACLData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadFrontendProductVisibilityData;
use Symfony\Component\HttpFoundation\Response;

class AjaxLineItemControllerAclTest extends WebTestCase
{
    /** @var Product */
    protected $product;

    /** @var ShoppingList */
    protected $shoppingList;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(
                LoadCustomerUserData::AUTH_USER,
                LoadCustomerUserData::AUTH_PW
            )
        );

        $this->loadFixtures([
            LoadShoppingListACLData::class,
            LoadShoppingListLineItemUserACLData::class,
            LoadFrontendProductVisibilityData::class,
        ]);

        $this->product = $this->getReference(LoadProductData::PRODUCT_1);
        $this->shoppingList = $this->getReference(LoadShoppingListACLData::SHOPPING_LIST_ACC_1_USER_LOCAL);
    }

    public function testAddProductFromView()
    {
        $this->ajaxRequest(
            'POST',
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
        $this->ajaxRequest(
            'DELETE',
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

    public function testRemoveLineItemAction()
    {
        $shoppingList = $this->getReference(LoadShoppingListACLData::SHOPPING_LIST_ACC_1_1_USER_LOCAL);
        $lineItem = $shoppingList->getLineItems()->first();

        $this->ajaxRequest(
            'DELETE',
            $this->getUrl(
                'oro_shopping_list_frontend_remove_line_item',
                [
                    'lineItemId' => $lineItem->getId(),
                ]
            )
        );
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);

        $this->ajaxRequest(
            'DELETE',
            $this->getUrl(
                'oro_shopping_list_frontend_remove_line_item',
                [
                    'lineItemId' => $lineItem->getId(),
                ]
            ),
            [],
            [],
            $this->generateBasicAuthHeader(
                LoadCustomerUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                LoadCustomerUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL
            )
        );
        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
    }

    public function testAddProductsMassAction()
    {
        $this->markTestSkipped('Enable in BB-5144');

        $this->ajaxRequest(
            'POST',
            $this->getUrl(
                'oro_shopping_list_add_products_massaction',
                [
                    'gridName' => 'frontend-product-search-grid',
                    'actionName' => 'oro_shoppinglist_frontend_addlineitemlist' . $this->shoppingList->getId(),
                    'shoppingList' => $this->shoppingList->getId(),
                    'inset' => 1,
                    'values' => $this->getReference('product-1')->getId(),
                ]
            )
        );

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
    }

    public function testAddProductsToNewMassAction()
    {
        $this->ajaxRequest(
            'POST',
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
