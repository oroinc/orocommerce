<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;

use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;

/**
 * @dbIsolation
 */
class ShoppingListAddButtonTest extends WebTestCase
{

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );
    }

    public function testCreateNewShoppingList()
    {
        $shoppingListRepo = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BShoppingListBundle:ShoppingList');
        
        $shoppingListsCount = count($shoppingListRepo->findAll());

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_shopping_list_frontend_create',
                [
                    'createOnly' => 'true',
                    '_widgetContainer' => 'dialog',
                    '_wid' => 'test-uuid'
                ]
            )
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Shopping List Name', $result->getContent());

        $form = $crawler->selectButton('Create')->form();
        $form['orob2b_shopping_list_type[label]'] = 'TestShoppingList';

        $this->client->request(
            $form->getMethod(),
            $this->getUrl(
                'orob2b_shopping_list_frontend_create',
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

    /**
     * @return ShoppingListRepository
     */
    protected function getShoppingListRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BShoppingListBundle:ShoppingList');
    }
}
