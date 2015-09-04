<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Component\Testing\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadUserData;

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
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions',
                'OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData',
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists',
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadUserData',
            ]
        );
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_shopping_list_frontend_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals('Shopping Lists', $crawler->filter('h1.oro-subtitle')->html());
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
        $response = $this->requestFrontendGrid(
            'frontend-shopping-list-grid',
            ['frontend-shopping-list-grid[_filter][label][value]' => self::TEST_LABEL1]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_shopping_list_frontend_update', ['id' => $result['id']])
        );
        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertShoppingListSave($crawler, self::TEST_LABEL2);
    }

    public function testView()
    {
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_shopping_list_frontend_view', ['id' => $shoppingList->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains($shoppingList->getLabel(), $html);

        $createRfpButton = (bool)$crawler->selectButton('Request a Quote')->count();

        $this->assertTrue($createRfpButton);
    }

    public function testSetCurrent()
    {
        $this->client->followRedirects(true);
        /** @var ShoppingList $list */
        $list = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $this->assertFalse($list->isCurrent());
        $this->client->request(
            'GET',
            $this->getUrl('orob2b_shopping_list_frontend_set_current', ['id' => $list->getId()])
        );
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        /** @var ShoppingList $updatedList */
        $updatedList = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList')
            ->getRepository('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList')->find($list->getId());
        $this->assertTrue($updatedList->isCurrent());
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

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider createRfpProvider
     */
    public function testCreateRfp(array $inputData, array $expectedData)
    {
        $this->initClient([], array_merge(
            $this->generateBasicAuthHeader($inputData['login'], $inputData['password']),
            ['HTTP_X-CSRF-Header' => 1]
        ));
        /* @var $shoppingList ShoppingList */
        $shoppingList = $this->getReference($inputData['shoppingList']);
        $this->client->request(
            'POST',
            $this->getUrl('orob2b_shopping_list_frontend_create_rfp', ['id' => $shoppingList->getId()])
        );
        $this->client->followRedirects(true);
        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, $expectedData['statusCode']);
    }

    /**
     * @return array
     */
    public function createRfpProvider()
    {
        return [
            'account1 user1 (Order:NONE)' => [
                'input' => [
                    'shoppingList' => LoadShoppingLists::SHOPPING_LIST_1,
                    'login' => LoadUserData::ACCOUNT1_USER1,
                    'password' => LoadUserData::ACCOUNT1_USER1,
                ],
                'expected' => [
                    'statusCode' => 403,
                ],
            ],
            'account1 user2 (Order:CREATE_BASIC)' => [
                'input' => [
                    'shoppingList' => LoadShoppingLists::SHOPPING_LIST_1,
                    'login' => LoadUserData::ACCOUNT1_USER2,
                    'password' => LoadUserData::ACCOUNT1_USER2,
                ],
                'expected' => [
                    'statusCode' => 200,
                ],
            ],
        ];
    }
}
