<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Controller\Frontend;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\ProductPriceReference;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\DomCrawler\Field\InputFormField;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @dbIsolationPerTest
 */
class RequestControllerTest extends WebTestCase
{
    use ProductPriceReference;
    use ConfigManagerAwareTestTrait;

    const PHONE = '2-(999)507-4625';
    const COMPANY = 'google';
    const ROLE = 'CEO';
    const REQUEST = 'request body';
    const PO_NUMBER = 'CA245566789KL';

    /** @var WorkflowManager */
    protected $manager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $this->manager = $this->getContainer()->get('oro_workflow.manager');

        $this->loadFixtures(
            [
                LoadUserData::class,
                LoadRequestData::class,
                LoadProductPrices::class,
            ]
        );
    }

    public function testGridForAnonymousUsers()
    {
        $response = $this->client->requestGrid(['gridName' => 'frontend-requests-grid'], [], true);
        $this->assertSame($response->getStatusCode(), 302);
    }

    public function testIndexNotFoundForAnonymousUsers()
    {
        $this->client->request('GET', $this->getUrl('oro_rfp_frontend_request_index'));
        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 404);
    }

    public function testIndexAccessDeniedForAnonymousUsers()
    {
        $configManager = self::getConfigManager('global');
        $configManager->set('oro_rfp.guest_rfp', true);
        $configManager->flush();

        $this->client->request('GET', $this->getUrl('oro_rfp_frontend_request_index'));
        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 401);

        $configManager->reset('oro_rfp.guest_rfp');
        $configManager->flush();
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     * @param bool $activateFrontoffice
     *
     * @dataProvider indexProvider
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function testIndex(array $inputData, array $expectedData, $activateFrontoffice = false)
    {
        if (!$activateFrontoffice) {
            $this->manager->deactivateWorkflow('b2b_rfq_frontoffice_default');
        }

        $this->loginUser($inputData['login']);

        $crawler = $this->client->request('GET', $this->getUrl('oro_rfp_frontend_request_index'));
        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), $expectedData['code']);

        if ($this->client->getResponse()->isRedirect()) {
            return;
        }

        static::assertStringContainsString('frontend-requests-grid', $crawler->html());

        $response = $this->client->requestFrontendGrid(['gridName' => 'frontend-requests-grid']);

        $result = static::getJsonResponseContent($response, 200);

        $data = $result['data'];

        if (isset($expectedData['columns'])) {
            static::assertNotEmpty($data);
            $testedColumns = array_keys($data[0]);
            $expectedColumns = $expectedData['columns'];

            sort($testedColumns);
            sort($expectedColumns);

            foreach ($data as $item) {
                static::assertEquals($expectedData['action_configuration'], $item['action_configuration']);
            }

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
                static::assertStringContainsString(
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
     * @dataProvider viewProvider
     */
    public function testView(array $inputData, array $expectedData)
    {
        $this->loginUser($inputData['login']);
        /* @var $request Request */
        $request = $this->getReference($inputData['request']);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_rfp_frontend_request_view',
                ['id' => $request->getId()]
            )
        );

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);
        $shouldContainText = [
            $request->getFirstName(),
            $request->getLastName(),
            $request->getEmail(),
            $request->getPoNumber(),
        ];
        if ($request->getShipUntil()) {
            $shouldContainText[] = $request->getShipUntil()->format('n/j/Y');
        }

        foreach ($shouldContainText as $expectedText) {
            static::assertStringContainsString(
                $expectedText,
                $result->getContent()
            );
        }

        if (isset($expectedData['columnsCount'])) {
            $controls = $crawler->filter('.customer-info-grid__row')->count();
            static::assertEquals($expectedData['columnsCount'], $controls);
        }

        if (isset($expectedData['hideButtonEdit'])) {
            $buttonEdit = $crawler->filter('.controls-list')->html();
            static::assertStringNotContainsString('edit', $buttonEdit);
        }
    }

    /**
     * @dataProvider actionsForDeletedRequestProvider
     *
     * @param array $input
     * @param string $path
     * @param string $code
     */
    public function testActionsForDeletedRequest(array $input, $path, $code)
    {
        $this->loginUser($input['login']);
        /* @var $request Request */
        $request = $this->getReference(LoadRequestData::REQUEST14);

        $this->client->request('GET', $this->getUrl($path, ['id' => $request->getId()]));

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), $code);
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function indexProvider()
    {
        return [
            'customer1 user1 (only customer user requests) and active frontoffice' => [
                'input' => [
                    'login' => LoadUserData::ACCOUNT1_USER1,
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
                        'poNumber',
                        'shipUntil',
                        'createdAt',
                        'update_aria_label',
                        'update_link',
                        'view_aria_label',
                        'view_link',
                        'workflowStepLabel',
                        'action_configuration',
                        'customerStatusName',
                    ],
                    'action_configuration' => [
                        'update' => false,
                        'delete' => false,
                    ],
                ],
                'activateFrontoffice' => true
            ],
            'customer1 user1 (only customer user requests)' => [
                'input' => [
                    'login' => LoadUserData::ACCOUNT1_USER1,
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
                        'poNumber',
                        'shipUntil',
                        'createdAt',
                        'update_aria_label',
                        'update_link',
                        'view_aria_label',
                        'view_link',
                        'action_configuration',
                        'customerStatusName',
                    ],
                    'action_configuration' => [
                        'delete' => false
                    ]
                ],
            ],
            'customer1 user2 (all customer requests)' => [
                'input' => [
                    'login' => LoadUserData::ACCOUNT1_USER2,
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
                        'poNumber',
                        'shipUntil',
                        'createdAt',
                        'customerUserName',
                        'update_aria_label',
                        'update_link',
                        'view_aria_label',
                        'view_link',
                        'action_configuration',
                        'customerStatusName',
                    ],
                    'action_configuration' => [
                        'update' => false,
                        'delete' => false
                    ]
                ],
            ],
            'customer1 user3 (all customer requests and submittedTo)' => [
                'input' => [
                    'login' => LoadUserData::ACCOUNT1_USER3,
                ],
                'expected' => [
                    'code' => 200,
                    'data' => [
                        LoadRequestData::REQUEST4,
                    ],
                    'columns' => [
                        'id',
                        'poNumber',
                        'shipUntil',
                        'createdAt',
                        'update_aria_label',
                        'update_link',
                        'view_aria_label',
                        'view_link',
                        'action_configuration',
                        'customerStatusName',
                    ],
                    'action_configuration' => [
                        'update' => false,
                        'delete' => false
                    ]
                ],
            ],
            'customer2 user1 (only customer user requests)' => [
                'input' => [
                    'login' => LoadUserData::ACCOUNT2_USER1,
                ],
                'expected' => [
                    'code' => 200,
                    'data' => [
                        LoadRequestData::REQUEST5,
                        LoadRequestData::REQUEST6,
                    ],
                    'columns' => [
                        'id',
                        'poNumber',
                        'shipUntil',
                        'createdAt',
                        'update_aria_label',
                        'update_link',
                        'view_aria_label',
                        'view_link',
                        'action_configuration',
                        'customerStatusName',
                    ],
                    'action_configuration' => [
                        'delete' => false
                    ]
                ],
            ],
            'customer2 user2 (all customer user requests and full permissions)' => [
                'input' => [
                    'login' => LoadUserData::ACCOUNT2_USER2,
                ],
                'expected' => [
                    'code' => 200,
                    'data' => [
                        LoadRequestData::REQUEST5,
                        LoadRequestData::REQUEST6,
                        LoadRequestData::REQUEST13
                    ],
                    'columns' => [
                        'id',
                        'poNumber',
                        'shipUntil',
                        'createdAt',
                        'update_aria_label',
                        'update_link',
                        'view_aria_label',
                        'view_link',
                        'action_configuration',
                        'customerUserName',
                        'customerStatusName',
                    ],
                    'action_configuration' => [
                        'delete' => false
                    ]
                ],
            ],
            'parent customer user1 (all requests)' => [
                'input' => [
                    'login' => LoadUserData::PARENT_ACCOUNT_USER1,
                ],
                'expected' => [
                    'code' => 200,
                    'data' => [
                        LoadRequestData::REQUEST2,
                        LoadRequestData::REQUEST3,
                        LoadRequestData::REQUEST4,
                        LoadRequestData::REQUEST5,
                        LoadRequestData::REQUEST6,
                        LoadRequestData::REQUEST7,
                        LoadRequestData::REQUEST8,
                        LoadRequestData::REQUEST10,
                        LoadRequestData::REQUEST11,
                        LoadRequestData::REQUEST12,
                        LoadRequestData::REQUEST13
                    ]
                ],
            ],
            'parent customer user2 (only customer user requests)' => [
                'input' => [
                    'login' => LoadUserData::PARENT_ACCOUNT_USER2,
                ],
                'expected' => [
                    'code' => 200,
                    'data' => [
                        LoadRequestData::REQUEST10,
                        LoadRequestData::REQUEST11,
                        LoadRequestData::REQUEST12
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
            'customer1 user1 (CustomerUser:VIEW_BASIC)' => [
                'input' => [
                    'request' => LoadRequestData::REQUEST2,
                    'login' => LoadUserData::ACCOUNT1_USER1,
                ],
                'expected' => [
                    'columnsCount' => 8,
                ],
            ],
            'customer1 user3 (CustomerUser:VIEW_LOCAL)' => [
                'input' => [
                    'request' => LoadRequestData::REQUEST2,
                    'login' => LoadUserData::ACCOUNT1_USER2,
                ],
                'expected' => [
                    'columnsCount' => 9,
                    'hideButtonEdit' => true
                ],
            ],
        ];
    }

    /**
     * @dataProvider ACLProvider
     *
     * @param string $route
     * @param string $request
     * @param string $login
     * @param int $status
     */
    public function testACL($route, $request, $login, $status)
    {
        if ('' !== $login) {
            $this->loginUser($login);
        } else {
            $this->initClient([]);
        }

        /* @var $request Request */
        $request = $this->getReference($request);

        $this->client->request(
            'GET',
            $this->getUrl(
                $route,
                ['id' => $request->getId()]
            )
        );

        $response = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($response, $status);
    }

    /**
     * @return array
     */
    public function ACLProvider()
    {
        return [
            'VIEW (anonymous user)' => [
                'route' => 'oro_rfp_frontend_request_view',
                'request' => LoadRequestData::REQUEST2,
                'login' => '',
                'status' => 404
            ],
            'UPDATE (anonymous user)' => [
                'route' => 'oro_rfp_frontend_request_update',
                'request' => LoadRequestData::REQUEST2,
                'login' => '',
                'status' => 404
            ],
            'VIEW (user from another customer)' => [
                'route' => 'oro_rfp_frontend_request_view',
                'request' => LoadRequestData::REQUEST2,
                'login' => LoadUserData::ACCOUNT2_USER1,
                'status' => 403
            ],
            'UPDATE (user from another customer)' => [
                'route' => 'oro_rfp_frontend_request_update',
                'request' => LoadRequestData::REQUEST2,
                'login' => LoadUserData::ACCOUNT2_USER1,
                'status' => 403
            ],
            'VIEW (user from parent customer : DEEP)' => [
                'route' => 'oro_rfp_frontend_request_view',
                'request' => LoadRequestData::REQUEST2,
                'login' => LoadUserData::PARENT_ACCOUNT_USER1,
                'status' => 200
            ],
            'UPDATE (user from parent customer : DEEP)' => [
                'route' => 'oro_rfp_frontend_request_update',
                'request' => LoadRequestData::REQUEST2,
                'login' => LoadUserData::PARENT_ACCOUNT_USER1,
                'status' => 403
            ],
            'VIEW (user from parent customer : LOCAL)' => [
                'route' => 'oro_rfp_frontend_request_view',
                'request' => LoadRequestData::REQUEST2,
                'login' => LoadUserData::PARENT_ACCOUNT_USER2,
                'status' => 403
            ],
            'UPDATE (user from parent customer : LOCAL)' => [
                'route' => 'oro_rfp_frontend_request_update',
                'request' => LoadRequestData::REQUEST2,
                'login' => LoadUserData::PARENT_ACCOUNT_USER2,
                'status' => 403
            ],
        ];
    }

    /**
     * @return \Generator
     */
    public function actionsForDeletedRequestProvider()
    {
        yield 'view action' => [
            'input' => [
                'login' => LoadUserData::ACCOUNT1_USER1,
            ],
            'path' => 'oro_rfp_frontend_request_view',
            'code' => 404
        ];
        yield 'update action' => [
            'input' => [
                'login' => LoadUserData::ACCOUNT1_USER1,
            ],
            'path' => 'oro_rfp_frontend_request_update',
            'code' => 403
        ];
    }

    /**
     * @dataProvider createProvider
     */
    public function testCreate(array $formData, array $expected)
    {
        $this->loginUser(LoadUserData::ACCOUNT1_USER1);

        $crawler = $this->client->request('GET', $this->getUrl('oro_rfp_frontend_request_create'));
        $form = $crawler->selectButton('Submit Request')->form();

        $crfToken = $this->getContainer()->get('security.csrf.token_manager')
            ->getToken('oro_rfp_frontend_request')
            ->getValue();

        /** @var ProductPrice $productPrice */
        $productPrice = $this->getPriceByReference('product_price.1');

        $parameters = [
            'input_action' => 'save_and_stay',
            'oro_rfp_frontend_request' => $formData
        ];
        $parameters['oro_rfp_frontend_request']['_token'] = $crfToken;
        $parameters['oro_rfp_frontend_request']['requestProducts'] = [
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
        static::assertStringContainsString('Request has been saved', $crawler->html());

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
     */
    public function testCreateQueryInit(array $productItems, array $expectedData)
    {
        $this->loginUser(LoadUserData::ACCOUNT1_USER1);

        $productIdCallable = function ($productReference) {
            return $this->getReference($productReference)->getId();
        };

        $productItems = array_combine(array_map($productIdCallable, array_keys($productItems)), $productItems);

        $crawler = $this->client->request('GET', $this->getUrl('oro_rfp_frontend_request_create', [
            'product_items' => $productItems,
        ]));

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Submit Request')->form();

        /** @var array $formRequestProducts */
        $formRequestProducts = $form->get('oro_rfp_frontend_request[requestProducts]');

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
                    'product-1' => [
                        ['unit' => 'liter', 'quantity' => 10]
                    ],
                    'product-2' => [
                        ['unit' => 'bottle', 'quantity' => 20],
                        ['unit' => 'box', 'quantity' => 2],
                    ],
                ],
                'expectedData' => [
                    'product-1' => [
                        ['unit' => 'liter', 'quantity' => 10]
                    ],
                    'product-2' => [
                        ['unit' => 'bottle', 'quantity' => 20],
                        ['unit' => 'box', 'quantity' => 2],
                    ],
                ]
            ],
            [
                'productLineItems' => [
                    'product-1' => [
                        ['unit' => 'no_unit', 'quantity' => 10],
                        ['unit' => 'liter', 'quantity' => 10],
                    ],
                    'product-2' => [
                        ['unit' => 'bottle'],
                    ],
                ],
                'expectedData' => [
                    'product-1' => [
                        ['unit' => 'liter', 'quantity' => 10]
                    ],
                ]
            ]
        ];
    }

    public function testUpdate()
    {
        $this->loginUser(LoadUserData::ACCOUNT2_USER1);
        $this->getContainer()->get('oro_workflow.manager')->deactivateWorkflow('b2b_rfq_frontoffice_default');

        $id = $this->getReference(LoadRequestData::REQUEST6)->getId();
        $crawler = $this->client->request('GET', $this->getUrl('oro_rfp_frontend_request_update', ['id' => $id]));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->selectButton('Submit Request')->form();

        $form['oro_rfp_frontend_request[firstName]'] = LoadRequestData::FIRST_NAME . '_UPDATE';
        $form['oro_rfp_frontend_request[lastName]'] = LoadRequestData::LAST_NAME . '_UPDATE';
        $form['oro_rfp_frontend_request[email]'] = LoadRequestData::EMAIL . '_UPDATE';
        $form['oro_rfp_frontend_request[poNumber]'] = LoadRequestData::PO_NUMBER . '_UPDATE';
        $form['oro_rfp_frontend_request[assignedCustomerUsers]'] = implode(',', [
            $this->getReference(LoadUserData::ACCOUNT1_USER1)->getId(),
            $this->getReference(LoadUserData::ACCOUNT1_USER2)->getId()
        ]);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString('Request has been saved', $crawler->html());

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

    public function testViewDeleted()
    {
        $this->loginUser(LoadUserData::ACCOUNT1_USER1);

        /* @var $request Request */
        $request = $this->getReference(LoadRequestData::REQUEST2);
        $id = $request->getId();

        $this->client->request('GET', $this->getUrl('oro_rfp_frontend_request_view', ['id' => $id]));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $em = $this->getManager(Request::class);
        $request = $em->find(Request::class, $id);

        $request->setInternalStatus(
            $this->getEnumEntity(Request::INTERNAL_STATUS_CODE, Request::INTERNAL_STATUS_DELETED)
        );

        $em->flush($request);

        $this->client->request('GET', $this->getUrl('oro_rfp_frontend_request_view', ['id' => $id]));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 404);
    }

    /**
     * @param string $enumField
     * @param string $enumCode
     * @return AbstractEnumValue
     */
    protected function getEnumEntity($enumField, $enumCode)
    {
        $className = ExtendHelper::buildEnumValueClassName($enumField);

        return $this->getManager($className)->getReference($className, $enumCode);
    }

    /**
     * @param string $className
     * @return EntityManager
     */
    protected function getManager($className)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($className);
    }

    /**
     * @param string $html
     * @param        $fields
     */
    protected function assertContainsRequestData($html, $fields)
    {
        foreach ($fields as $fieldValue) {
            static::assertStringContainsString($fieldValue, $html);
        }
    }
}
