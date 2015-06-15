<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Controller;

use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class ShoppingListControllerTest extends WebTestCase
{
    /**
     * @var NameFormatter
     */
    protected $formatter;

    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));

        $this->loadFixtures(
            [
                'Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData',
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists'
            ]
        );

        $this->formatter = $this->getContainer()->get('oro_locale.twig.name');
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('orob2b_shopping_list_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testView()
    {
        $shoppingList = $this->getReference('shopping_list');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_shopping_list_view', ['id' => $shoppingList->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains($shoppingList->getLabel(), $html);
    }
}
