<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\AccountUserShoppingListsProvider;

/**
 * @dbIsolation
 */
class AccountUserShoppingListsProviderTest extends WebTestCase
{
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
                'Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists'
            ]
        );

        $this->dataProvider = $this->getContainer()
            ->get('orob2b_shopping_list.layout.data_provider.account_user_shopping_lists');
    }

    public function testGetShoppingLists()
    {
        $this->client->request('GET', $this->getUrl('orob2b_frontend_root'));

        $actual = $this->dataProvider->getShoppingLists();

        $this->assertNotEmpty($actual);
    }
}
