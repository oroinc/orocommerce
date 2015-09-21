<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\Controller\Frontend;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Model\DataStorageAwareProcessor;
use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadUserData;

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

        $this->loadFixtures([
            'OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadUserData',
            'OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData',
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices'
        ]);
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

        $response = $this->requestFrontendGrid([
            'gridName' => 'frontend-requests-grid',
            'frontend-requests-grid[_sort_by][id]' => 'ASC',
        ]);

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

        $crawler = $this->client->request('GET', $this->getUrl(
            'orob2b_rfp_frontend_request_view',
            ['id' => $request->getId()]
        ));

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);

        $controls = $crawler->filter('.control-group');

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
            'not logged in' => [
                'input' => [
                    'login' => null,
                ],
                'expected' => [
                    'code' => 302,
                ],
            ],
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

    public function testQuickAdd()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_frontend_quick_add'));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->filter('form[name="orob2b_product_quick_add"]')->form();

        /** @var Product $product */
        $product = $this->getReference('product.3');

        $products = [
            [
                'productSku' => $product->getSku(),
                'productQuantity' => 15
            ]
        ];

        /** @var DataStorageAwareProcessor $processor */
        $processor = $this->getContainer()->get('orob2b_rfp.processor.quick_add');

        $this->client->followRedirects(true);
        $crawler = $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            [
                'orob2b_product_quick_add' => [
                    '_token' => $form['orob2b_product_quick_add[_token]']->getValue(),
                    'products' => $products,
                    'component' => $processor->getName()
                ]
            ]
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $expectedQuickAddLineItems = [
            [
                'product' => $product->getId(),
                'quantity' => 15,
            ]
        ];

        $this->assertEquals($expectedQuickAddLineItems, $this->getActualLineItems($crawler, 1));

        $form = $crawler->selectButton('Submit')->form();
        $form['orob2b_rfp_frontend_request_type[firstName]'] = 'Firstname';
        $form['orob2b_rfp_frontend_request_type[lastName]'] = 'Lastname';
        $form['orob2b_rfp_frontend_request_type[email]'] = 'email@example.com';
        $form['orob2b_rfp_frontend_request_type[phone]'] = '55555555';
        $form['orob2b_rfp_frontend_request_type[company]'] = 'Test Company';
        $form['orob2b_rfp_frontend_request_type[role]'] = 'Test Role';
        $form['orob2b_rfp_frontend_request_type[body]'] = 'Test Body';
        $form['orob2b_rfp_frontend_request_type[requestProducts][0][requestProductItems][0][price][value]'] = 100;
        $form['orob2b_rfp_frontend_request_type[requestProducts][0][requestProductItems][0][price][currency]'] = 'USD';

        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('Request has been saved', $crawler->html());
    }

    /**
     * @param Crawler $crawler
     * @param int $count
     * @return array
     */
    protected function getActualLineItems(Crawler $crawler, $count)
    {
        $result = [];
        $basePath = 'input[name="orob2b_rfp_frontend_request_type[requestProducts]';

        for ($i = 0; $i < $count; $i++) {
            $result[] = [
                'product' => $crawler->filter($basePath .'['.$i.'][product]"]')->extract('value')[0],
                'quantity' => $crawler->filter($basePath .'['.$i.'][requestProductItems][0][quantity]"]')
                    ->extract('value')[0]
            ];
        }

        return $result;
    }
}
