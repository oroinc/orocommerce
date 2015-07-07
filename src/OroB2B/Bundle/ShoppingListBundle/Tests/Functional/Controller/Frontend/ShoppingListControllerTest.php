<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

/**
 * @dbIsolation
 */
class ShoppingListControllerTest extends WebTestCase
{
    const TEST_LABEL1 = 'Shopping list label 1';
    const TEST_LABEL2 = 'Shopping list label 2';

    protected function setUp()
    {
        $this->initClient(
            [],
            array_merge(
                $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW),
                ['HTTP_X-CSRF-Header' => 1]
            )
        );

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists'
            ]
        );
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_shopping_list_frontend_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals("Shopping Lists", $crawler->filter('h1.oro-subtitle')->html());
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_shopping_list_frontend_create'));
        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertShoppingListSave($crawler, self::TEST_LABEL1);
    }

    public function testUpdate()
    {
        $this->markTestSkipped(
            'Skipped because of requestGrid helper methods works now only for admin part. Test will be fixed in BB-750'
        );

        $response = $this->client->requestGrid(
            'account-user-shopping-list-grid',
            ['account-user-shopping-list-grid[_filter][label][value]' => self::TEST_LABEL1]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $id      = $result['id'];
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_shopping_list_frontend_update', ['id' => $result['id']])
        );
        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertShoppingListSave($crawler, self::TEST_LABEL2);

        return $id;
    }

    public function testView()
    {
        $shoppingList = $this->getReference('shopping_list');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_shopping_list_frontend_view', ['id' => $shoppingList->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains($shoppingList->getLabel(), $html);
    }

    /**
     * @param Crawler $crawler
     * @param string  $label
     */
    protected function assertShoppingListSave(Crawler $crawler, $label)
    {
        $form = $crawler->selectButton('Save and Close')->form(
            [
                'orob2b_shopping_list_type[label]' => $label,
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains('Shopping List has been saved', $html);
    }
}
