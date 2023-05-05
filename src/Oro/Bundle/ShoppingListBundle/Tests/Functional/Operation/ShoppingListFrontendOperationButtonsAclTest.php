<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Operation;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserACLData;
use Oro\Bundle\FrontendBundle\Tests\Functional\FrontendActionTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListACLData;

class ShoppingListFrontendOperationButtonsAclTest extends FrontendActionTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadShoppingListACLData::class,
            LoadProductData::class,
        ]);

        $user = self::getContainer()->get('doctrine')->getRepository(CustomerUser::class)
            ->findOneBy(['email' => LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_LOCAL]);
        $token = new UsernamePasswordOrganizationToken(
            $user,
            false,
            'k',
            $user->getOrganization(),
            $user->getUserRoles()
        );
        self::getContainer()->get('security.token_storage')->setToken($token);
    }

    /**
     * @dataProvider lineItemOperationButtonsProvider
     */
    public function testLineItemOperationButtons(string $operationName, array $params)
    {
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $this->assertActionButton($operationName, $product->getId(), Product::class, $params);
    }

    public function lineItemOperationButtonsProvider(): array
    {
        return [
            [
                'operation' => 'oro_shoppinglist_frontend_products_add_to_shoppinglist',
                ['route' => 'oro_product_frontend_quick_add'],
            ],
            [
                'operation' => 'oro_shoppinglist_frontend_addlineitem',
                ['datagrid' => 'frontend-product-search-grid'],
            ],
        ];
    }
}
