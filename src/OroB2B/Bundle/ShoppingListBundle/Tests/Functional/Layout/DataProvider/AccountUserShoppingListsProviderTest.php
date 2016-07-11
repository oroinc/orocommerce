<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Layout\DataProvider;

use Oro\Component\Layout\LayoutContext;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider\AccountUserShoppingListsProvider;

/**
 * @dbIsolation
 */
class AccountUserShoppingListsProviderTest extends WebTestCase
{
    /**
     * @var LayoutContext
     */
    protected $context;

    /**
     * @var AccountUserShoppingListsProvider
     */
    protected $dataProvider;

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );
        $this->loadFixtures(
            [
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists'
            ]
        );

        $this->context = new LayoutContext();
        $this->dataProvider = $this->getContainer()
            ->get('orob2b_shopping_list.layout.data_provider.account_user_shopping_lists');
    }

    public function testGetData()
    {
        $this->client->request('GET', $this->getUrl('orob2b_frontend_root'));

        $actual = $this->dataProvider->getData($this->context);

        $this->assertInternalType('array', $actual);

        $this->assertArrayHasKey('shoppingLists', $actual);
        $this->assertNotEmpty($actual['shoppingLists']);
    }
}
