<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Provider;

use Oro\Component\Layout\LayoutContext;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ShoppingListBundle\Provider\AccountUserShoppingListsProvider;

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

        $this->client->request('GET', $this->getUrl('_frontend'));

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists'
            ]
        );

        $this->context = new LayoutContext();
        $this->dataProvider = $this->getContainer()->get('orob2b_shopping_list.provider.account_user_shopping_lists');
    }

    public function testGetIdentifier()
    {
        $this->assertEquals('orob2b_shopping_list_account_user_shopping_lists', $this->dataProvider->getIdentifier());
    }

    public function testGetData()
    {
        $actual = $this->dataProvider->getData($this->context);

        $this->assertInternalType('array', $actual);

        $this->assertArrayHasKey('allShoppingLists', $actual);
        $this->assertNotEmpty($actual['allShoppingLists']);

        $this->assertArrayHasKey('shoppingListsExceptedCurrent', $actual);
        $this->assertNotEmpty($actual['shoppingListsExceptedCurrent']);

        $this->assertArrayHasKey('currentShoppingList', $actual);
        $this->assertNotEmpty($actual['currentShoppingList']);
    }
}
