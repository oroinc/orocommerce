<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\Controller\Frontend;

use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadUserData;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

/**
 * @dbIsolation
 */
class RequestControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                'OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadUserData',
                'OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices',
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists',
            ]
        );
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider indexProvider
     */
    public function testIndex(array $inputData, array $expectedData)
    {
        $authParams = $inputData['login']
            ? static::generateBasicAuthHeader($inputData['login'], $inputData['password'])
            : [];
        $this->initClient([], $authParams);

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_rfp_frontend_request_index'));
        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), $expectedData['code']);

        if ($this->client->getResponse()->isRedirect()) {
            return;
        }

        static::assertContains('frontend-requests-grid', $crawler->html());

        $response = $this->requestFrontendGrid(
            [
                'gridName' => 'frontend-requests-grid',
                'frontend-requests-grid[_sort_by][id]' => 'ASC',
            ]
        );

        $result = static::getJsonResponseContent($response, 200);

        $data = $result['data'];

        if (isset($expectedData['columns'])) {
            static::assertNotEmpty($data);
            $testedColumns = array_keys($data[0]);
            $expectedColumns = $expectedData['columns'];

            sort($testedColumns);
            sort($expectedColumns);

            static::assertEquals($expectedColumns, $testedColumns);
        }

        $testedIds = [];
        foreach ($data as $row) {
            $testedIds[] = (int)$row['id'];
        }

        $expectedIds = [];
        foreach ($expectedData['data'] as $row) {
            /** @var Request $request */
            $request = $this->getReference($row);
            $expectedIds[] = $request->getId();
        }

        sort($expectedIds);
        sort($testedIds);

        static::assertEquals($expectedIds, $testedIds);
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider viewProvider
     */
    public function testView(array $inputData, array $expectedData)
    {
        $this->initClient([], static::generateBasicAuthHeader($inputData['login'], $inputData['password']));

        /* @var $request Request */
        $request = $this->getReference($inputData['request']);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_rfp_frontend_request_view',
                ['id' => $request->getId()]
            )
        );

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);

        $controls = $crawler->filter('.control-group');

        static::assertEquals($expectedData['columnsCount'], count($controls));
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider createFromShoppingListProvider
     */
    public function testCreateFromShoppingList(array $inputData, array $expectedData)
    {
        $this->initClient([], static::generateBasicAuthHeader($inputData['login'], $inputData['password']));
        /* @var $shoppingList ShoppingList */
        $shoppingList = $this->getReference($inputData['shoppingList']);
        $this->client->request(
            'POST',
            $this->getUrl('orob2b_rfp_frontend_createfromshoppinglist', ['id' => $shoppingList->getId()])
        );
        $this->client->followRedirects(true);
        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, $expectedData['statusCode']);
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider createFromShoppingListProvider
     */
    public function testCreateFromShoppingListForm(array $inputData, array $expectedData)
    {
        $this->initClient([], static::generateBasicAuthHeader($inputData['login'], $inputData['password']));

        /* @var $shoppingList ShoppingList */
        $shoppingList = $this->getReference($inputData['shoppingList']);

        $crawler = $this->client->request('GET', $this->getUrl(
            'orob2b_rfp_frontend_createfromshoppinglistform',
            ['id' => $shoppingList->getId()]
        ));

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, $expectedData['statusCode']);
        static::assertCount($expectedData['buttonsCount'], $crawler->selectButton('Request a Quote'));
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function indexProvider()
    {
        return [
            'account1 user1 (only account user requests)' => [
                'input' => [
                    'login' => LoadUserData::ACCOUNT1_USER1,
                    'password' => LoadUserData::ACCOUNT1_USER1,
                ],
                'expected' => [
                    'code' => 200,
                    'data' => [
                        LoadRequestData::REQUEST2,
                        LoadRequestData::REQUEST7,
                        LoadRequestData::REQUEST8,
                    ],
                    'columns' => [
                        'id',
                        'isDraft',
                        'createdAt',
                        'update_link',
                        'view_link',
                        'action_configuration',
                    ],
                ],
            ],
            'account1 user2 (all account requests)' => [
                'input' => [
                    'login' => LoadUserData::ACCOUNT1_USER2,
                    'password' => LoadUserData::ACCOUNT1_USER2,
                ],
                'expected' => [
                    'code' => 200,
                    'data' => [
                        LoadRequestData::REQUEST2,
                        LoadRequestData::REQUEST3,
                        LoadRequestData::REQUEST4,
                        LoadRequestData::REQUEST7,
                        LoadRequestData::REQUEST8,
                    ],
                    'columns' => [
                        'id',
                        'isDraft',
                        'createdAt',
                        'accountUserName',
                        'update_link',
                        'view_link',
                        'action_configuration',
                    ],
                ],
            ],
            'account1 user3 (all account requests and submittedTo)' => [
                'input' => [
                    'login' => LoadUserData::ACCOUNT1_USER3,
                    'password' => LoadUserData::ACCOUNT1_USER3,
                ],
                'expected' => [
                    'code' => 200,
                    'data' => [
                        LoadRequestData::REQUEST4,
                    ],
                    'columns' => [
                        'id',
                        'isDraft',
                        'createdAt',
                        'update_link',
                        'view_link',
                        'action_configuration',
                    ],
                ],
            ],
            'account2 user1 (only account user requests)' => [
                'input' => [
                    'login' => LoadUserData::ACCOUNT2_USER1,
                    'password' => LoadUserData::ACCOUNT2_USER1,
                ],
                'expected' => [
                    'code' => 200,
                    'data' => [
                        LoadRequestData::REQUEST5,
                        LoadRequestData::REQUEST6,
                    ],
                    'columns' => [
                        'id',
                        'isDraft',
                        'createdAt',
                        'update_link',
                        'view_link',
                        'action_configuration',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function viewProvider()
    {
        return [
            'account1 user1 (AccountUser:VIEW_BASIC)' => [
                'input' => [
                    'request' => LoadRequestData::REQUEST2,
                    'login' => LoadUserData::ACCOUNT1_USER1,
                    'password' => LoadUserData::ACCOUNT1_USER1,
                ],
                'expected' => [
                    'columnsCount' => 7,
                ],
            ],
            'account1 user3 (AccountUser:VIEW_LOCAL)' => [
                'input' => [
                    'request' => LoadRequestData::REQUEST2,
                    'login' => LoadUserData::ACCOUNT1_USER2,
                    'password' => LoadUserData::ACCOUNT1_USER2,
                ],
                'expected' => [
                    'columnsCount' => 8,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function createFromShoppingListProvider()
    {
        return [
            'account1 user2 (RFP:NONE)' => [
                'input' => [
                    'shoppingList' => LoadShoppingLists::SHOPPING_LIST_1,
                    'login' => LoadUserData::ACCOUNT1_USER2,
                    'password' => LoadUserData::ACCOUNT1_USER2,
                ],
                'expected' => [
                    'statusCode' => 403,
                    'buttonsCount' => 0,
                ],
            ],
            'account1 user1 (RFP:CREATE_BASIC)' => [
                'input' => [
                    'shoppingList' => LoadShoppingLists::SHOPPING_LIST_1,
                    'login' => LoadUserData::ACCOUNT1_USER1,
                    'password' => LoadUserData::ACCOUNT1_USER1,
                ],
                'expected' => [
                    'statusCode' => 200,
                    'buttonsCount' => 1,
                ],
            ],
        ];
    }
}
