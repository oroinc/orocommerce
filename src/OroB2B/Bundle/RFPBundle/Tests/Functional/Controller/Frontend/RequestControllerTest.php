<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\Controller\Frontend;

use Symfony\Component\DomCrawler\Field\InputFormField;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadUserData;

/**
 * @dbIsolation
 */
class RequestControllerTest extends WebTestCase
{
    const PHONE = '2-(999)507-4625';
    const COMPANY = 'google';
    const ROLE = 'CEO';
    const REQUEST = 'request body';
    const PO_NUMBER = 'CA245566789KL';

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

        $response = $this->client->requestGrid(
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
        $testedData = [];

        foreach ($data as $row) {
            $testedIds[] = (int)$row['id'];
            $testedData[$row['id']] = $row;
        }

        $expectedIds = [];
        foreach ($expectedData['data'] as $row) {
            /** @var Request $request */
            $request = $this->getReference($row);
            $expectedIds[] = $request->getId();

            $this->assertEquals($request->getPoNumber(), $testedData[$request->getId()]['poNumber']);
            if ($request->getShipUntil()) {
                $this->assertContains(
                    $request->getShipUntil()->format('Y-m-d'),
                    $testedData[$request->getId()]['shipUntil']
                );
            } else {
                $this->assertEquals($request->getShipUntil(), $testedData[$request->getId()]['shipUntil']);
            }
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

        $this->assertContains($request->getFirstName(), $result->getContent());
        $this->assertContains($request->getLastName(), $result->getContent());
        $this->assertContains($request->getEmail(), $result->getContent());
        $this->assertContains($request->getPoNumber(), $result->getContent());

        if ($request->getShipUntil()) {
            $this->assertContains($request->getShipUntil()->format('M j, Y'), $result->getContent());
        }

        $controls = $crawler->filter('.account-oq__order-info__control');
        static::assertEquals($expectedData['columnsCount'], count($controls));
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
                        'statusLabel',
                        'poNumber',
                        'shipUntil',
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
                        'statusLabel',
                        'poNumber',
                        'shipUntil',
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
                        'statusLabel',
                        'poNumber',
                        'shipUntil',
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
                        'statusLabel',
                        'poNumber',
                        'shipUntil',
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
                    'columnsCount' => 9,
                ],
            ],
            'account1 user3 (AccountUser:VIEW_LOCAL)' => [
                'input' => [
                    'request' => LoadRequestData::REQUEST2,
                    'login' => LoadUserData::ACCOUNT1_USER2,
                    'password' => LoadUserData::ACCOUNT1_USER2,
                ],
                'expected' => [
                    'columnsCount' => 10,
                ],
            ],
        ];
    }

    /**
     * @param array $formData
     * @param array $expected
     * @dataProvider createProvider
     */
    public function testCreate(array $formData, array $expected)
    {
        $authParams = static::generateBasicAuthHeader(LoadUserData::ACCOUNT1_USER1, LoadUserData::ACCOUNT1_USER1);
        $this->initClient([], $authParams);

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_rfp_frontend_request_create'));
        $form = $crawler->selectButton('Submit Request For Quote')->form();

        $crfToken = $this->getContainer()->get('security.csrf.token_manager')->getToken('orob2b_rfp_frontend_request');

        /** @var ProductPrice $productPrice */
        $productPrice = $this->getReference('product_price.1');

        $parameters = [
            'input_action' => 'save_and_stay',
            'orob2b_rfp_frontend_request' => $formData
        ];
        $parameters['orob2b_rfp_frontend_request']['_token'] = $crfToken;
        $parameters['orob2b_rfp_frontend_request']['requestProducts'] = [
            [
                'product' => $productPrice->getProduct()->getId(),
                'requestProductItems' => [
                    [
                        'quantity' => $productPrice->getQuantity(),
                        'productUnit' => $productPrice->getUnit()->getCode(),
                        'price' => [
                            'value' => $productPrice->getPrice()->getValue(),
                            'currency' => $productPrice->getPrice()->getCurrency()
                        ]
                    ]
                ]
            ]
        ];

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $parameters);

        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Request has been saved', $crawler->html());

        $this->assertContainsRequestData($result->getContent(), $expected);
    }

    /**
     * @return array
     */
    public function createProvider()
    {
        return [
            'create' => [
                'formData' => [
                    'firstName' => LoadRequestData::FIRST_NAME,
                    'lastName' => LoadRequestData::LAST_NAME,
                    'email' => LoadRequestData::EMAIL,
                    'phone' => static::PHONE,
                    'role' => static::ROLE,
                    'company' => static::COMPANY,
                    'note' => static::REQUEST,
                    'poNumber' => static::PO_NUMBER,
                ],
                'expected' => [
                    'firstName' => LoadRequestData::FIRST_NAME,
                    'lastName' => LoadRequestData::LAST_NAME,
                    'email' => LoadRequestData::EMAIL,
                    'phone' => static::PHONE,
                    'role' => static::ROLE,
                    'company' => static::COMPANY,
                    'note' => static::REQUEST,
                    'poNumber' => static::PO_NUMBER
                ]
            ],
        ];
    }

    /**
     * @dataProvider createQueryInitDataProvider
     * @param array $productItems
     * @param array $expectedData
     */
    public function testCreateQueryInit(array $productItems, array $expectedData)
    {
        $authParams = static::generateBasicAuthHeader(LoadUserData::ACCOUNT1_USER1, LoadUserData::ACCOUNT1_USER1);
        $this->initClient([], $authParams);

        $productIdCallable = function ($productReference) {
            return $this->getReference($productReference)->getId();
        };

        $productItems = array_combine(array_map($productIdCallable, array_keys($productItems)), $productItems);

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_rfp_frontend_request_create', [
            'product_items' => $productItems,
        ]));

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Submit Request For Quote')->form();

        /** @var array $formRequestProducts */
        $formRequestProducts = $form->get('orob2b_rfp_frontend_request[requestProducts]');

        $formRequestProducts = array_reduce($formRequestProducts, function (array $result, array $formRequestProduct) {
            /** @var InputFormField $requestProduct */
            $requestProduct = $formRequestProduct['product'];
            $result[(string)$requestProduct->getValue()] = array_map(function (array $requestProductItem) {
                /** @var InputFormField $quantity */
                $quantity = $requestProductItem['quantity'];
                /** @var InputFormField $unit */
                $unit = $requestProductItem['productUnit'];
                return [
                    'unit' => $unit->getValue(),
                    'quantity' => $quantity->getValue(),
                ];
            }, $formRequestProduct['requestProductItems']);
            return $result;
        }, []);
        $expectedData = array_combine(array_map($productIdCallable, array_keys($expectedData)), $expectedData);
        $this->assertEquals($expectedData, $formRequestProducts);
    }

    /**
     * @return array
     */
    public function createQueryInitDataProvider()
    {
        return [
            [
                'productLineItems' => [
                    'product.1' => [
                        ['unit' => 'liter', 'quantity' => 10]
                    ],
                    'product.2' => [
                        ['unit' => 'bottle', 'quantity' => 20],
                        ['unit' => 'box', 'quantity' => 2],
                    ],
                ],
                'expectedData' => [
                    'product.1' => [
                        ['unit' => 'liter', 'quantity' => 10]
                    ],
                    'product.2' => [
                        ['unit' => 'bottle', 'quantity' => 20],
                        ['unit' => 'box', 'quantity' => 2],
                    ],
                ]
            ],
            [
                'productLineItems' => [
                    'product.1' => [
                        ['unit' => 'no_unit', 'quantity' => 10],
                        ['unit' => 'liter', 'quantity' => 10],
                    ],
                    'product.2' => [
                        ['unit' => 'bottle'],
                    ],
                ],
                'expectedData' => [
                    'product.1' => [
                        ['unit' => 'liter', 'quantity' => 10]
                    ],
                ]
            ]
        ];
    }

    public function testUpdate()
    {
        $authParams = static::generateBasicAuthHeader(LoadUserData::ACCOUNT1_USER1, LoadUserData::ACCOUNT1_USER1);
        $this->initClient([], $authParams);

        $response = $this->client->requestGrid(
            'frontend-requests-grid',
            [
                'frontend-requests-grid[_filter][poNumber][value]' => static::PO_NUMBER
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $id = $result['id'];
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_rfp_frontend_request_update', ['id' => $id]));

        $form = $crawler->selectButton('Submit Request For Quote')->form();

        $form['orob2b_rfp_frontend_request[firstName]'] = LoadRequestData::FIRST_NAME . '_UPDATE';
        $form['orob2b_rfp_frontend_request[lastName]'] = LoadRequestData::LAST_NAME . '_UPDATE';
        $form['orob2b_rfp_frontend_request[email]'] = LoadRequestData::EMAIL . '_UPDATE';
        $form['orob2b_rfp_frontend_request[poNumber]'] = LoadRequestData::PO_NUMBER . '_UPDATE';
        $form['orob2b_rfp_frontend_request[assignedAccountUsers]'] = implode(',', [
            $this->getReference(LoadUserData::ACCOUNT1_USER1)->getId(),
            $this->getReference(LoadUserData::ACCOUNT1_USER2)->getId()
        ]);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Request has been saved', $crawler->html());

        $this->assertContainsRequestData(
            $result->getContent(),
            [
                LoadRequestData::FIRST_NAME . '_UPDATE',
                LoadRequestData::LAST_NAME . '_UPDATE',
                LoadRequestData::EMAIL . '_UPDATE',
                LoadRequestData::PO_NUMBER . '_UPDATE',
                $this->getReference(LoadUserData::ACCOUNT1_USER1)->getFullName(),
                $this->getReference(LoadUserData::ACCOUNT1_USER2)->getFullName()
            ]
        );
    }

    /**
     * @param string $html
     * @param        $fields
     */
    protected function assertContainsRequestData($html, $fields)
    {
        foreach ($fields as $fieldValue) {
            $this->assertContains($fieldValue, $html);
        }
    }
}
