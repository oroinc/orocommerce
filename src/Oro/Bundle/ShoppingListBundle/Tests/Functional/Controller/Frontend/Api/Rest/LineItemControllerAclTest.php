<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend\Api\Rest;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserACLData;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ProductBundle\Form\Type\FrontendLineItemType;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListACLData;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class LineItemControllerAclTest extends WebTestCase
{
    use RolePermissionExtension;

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
            LoadShoppingListLineItems::class,
            LoadProductUnits::class
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

    public function testPut()
    {
        /* @var $lineItem LineItem */
        $lineItem = $this->getReference('shopping_list_line_item.1');
        $productUnit = $this->getReference('product_unit.bottle');
        $updatedLineItem = [FrontendLineItemType::NAME => ['unit' => $productUnit->getCode(), 'quantity' => 2]];

        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_shopping_list_frontend_put_line_item', ['id' => $lineItem->getId()]),
            $updatedLineItem
        );

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
    }

    public function testDeleteAccessDenied()
    {
        /* @var $lineItem LineItem */
        $lineItem = $this->getReference('shopping_list_line_item.1');

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_shopping_list_frontend_delete_line_item', ['id' => $lineItem->getId()]),
            [],
            [],
            $this->generateBasicAuthHeader(
                LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_LOCAL
            )
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
    }

    public function testPutAccessDenied()
    {
        /* @var $lineItem LineItem */
        $lineItem = $this->getReference('shopping_list_line_item.1');
        $productUnit = $this->getReference('product_unit.bottle');
        $updatedLineItem = [FrontendLineItemType::NAME => ['unit' => $productUnit->getCode(), 'quantity' => 2]];

        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_shopping_list_frontend_put_line_item', ['id' => $lineItem->getId()]),
            $updatedLineItem,
            [],
            $this->generateBasicAuthHeader(
                LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_LOCAL
            )
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
    }

    public function testDeleteAccessDeniedByLineItemACL()
    {
        /* @var $lineItem LineItem */
        $lineItem = $this->getReference('shopping_list_line_item.1');
        $role = $lineItem->getCustomerUser()->getRoles()[0];

        $this->updateRolePermission($role->getRole(), LineItem::class, AccessLevel::NONE_LEVEL, 'DELETE');
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_shopping_list_frontend_delete_line_item', ['id' => $lineItem->getId()])
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
    }

    public function testPutAccessDeniedByLineItemACL()
    {
        /* @var $lineItem LineItem */
        $lineItem = $this->getReference('shopping_list_line_item.1');
        $role = $lineItem->getCustomerUser()->getRoles()[0];
        $productUnit = $this->getReference('product_unit.bottle');
        $updatedLineItem = [FrontendLineItemType::NAME => ['unit' => $productUnit->getCode(), 'quantity' => 2]];

        $this->updateRolePermission($role->getRole(), LineItem::class, AccessLevel::NONE_LEVEL, 'DELETE');
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_shopping_list_frontend_put_line_item', ['id' => $lineItem->getId()]),
            $updatedLineItem
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
    }
}
