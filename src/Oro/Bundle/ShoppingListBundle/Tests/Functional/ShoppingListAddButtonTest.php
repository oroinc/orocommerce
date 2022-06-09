<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ShoppingListAddButtonTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
    }

    public function testCreateNewShoppingList()
    {
        $shoppingListRepo = $this->getContainer()->get('doctrine')
            ->getRepository(ShoppingList::class);

        $shoppingListsCount = count($shoppingListRepo->findAll());

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_shopping_list_frontend_create',
                [
                    'createOnly' => 'true',
                    '_widgetContainer' => 'dialog',
                    '_wid' => 'test-uuid'
                ]
            )
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('Shopping List Name', $result->getContent());

        $form = $crawler->selectButton('Create')->form();
        $form['oro_shopping_list_type[label]'] = 'TestShoppingList';

        $this->client->request(
            $form->getMethod(),
            $this->getUrl(
                'oro_shopping_list_frontend_create',
                [
                    'createOnly' => 'true',
                    '_widgetContainer' => 'dialog',
                    '_wid' => 'test-uuid'
                ]
            ),
            $form->getPhpValues()
        );
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $shoppingLists = $shoppingListRepo->findBy([], ['id' => 'DESC']);
        $this->assertCount($shoppingListsCount + 1, $shoppingLists);
    }
}
