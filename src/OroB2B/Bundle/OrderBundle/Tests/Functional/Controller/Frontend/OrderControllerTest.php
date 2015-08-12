<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\Controller\Frontend;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderUsers;
use OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;

/**
 * @dbIsolation
 *
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class OrderControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([
            'OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderUsers',
            'OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders',
        ]);
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider viewProvider
     */
    public function testView(array $inputData, array $expectedData)
    {
        $this->initClient([], array_merge(
            $this->generateBasicAuthHeader($inputData['login'], $inputData['password']),
            ['HTTP_X-CSRF-Header' => 1]
        ));

        /* @var $order Order */
        $order = $this->getReference($inputData['identifier']);

        $crawler = $this->client->request('GET', $this->getUrl(
            'orob2b_order_frontend_view',
            ['id' => $order->getId()]
        ));

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);

        $controls = $crawler->filter('.control-group');

        $this->assertEquals(count($expectedData['columns']), count($controls));

        /* @var $translator TranslatorInterface */
        $translator = $this->getContainer()->get('translator');

        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($controls as $key => $control) {
            /* @var $control \DOMElement */
            $column = $expectedData['columns'][$key];

            $label = $translator->trans($column['label']);
            $property = (string)$accessor->getValue($order, $column['property']) ?: $translator->trans('N/A');

            $this->assertContains($label, $control->textContent);
            $this->assertContains($property, $control->textContent);
        }
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider indexProvider
     */
    public function testIndex(array $inputData, array $expectedData)
    {
        $this->initClient([], array_merge(
            $this->generateBasicAuthHeader($inputData['login'], $inputData['password']),
            ['HTTP_X-CSRF-Header' => 1]
        ));

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_frontend_index'));

        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('frontend-orders-grid', $crawler->html());

        $response = $this->requestFrontendGrid([
            'gridName' => 'frontend-orders-grid',
            'frontend-orders-grid[_sort_by][id]' => 'ASC',
        ]);

        $result = static::getJsonResponseContent($response, 200);

        $data = $result['data'];

        $this->assertEquals(count($expectedData['data']), count($data));

        if (isset($expectedData['columns'])) {
            $testedColumns = array_keys($data[0]);
            $expectedColumns = $expectedData['columns'];

            sort($testedColumns);
            sort($expectedColumns);

            $this->assertEquals($expectedColumns, $testedColumns);
        }

        for ($i = 0; $i < count($expectedData['data']); $i++) {
            foreach ($expectedData['data'][$i] as $key => $value) {
                $this->assertArrayHasKey($key, $data[$i]);
                $this->assertEquals($value, $data[$i][$key]);
            }
        }
    }

    /**
     * @return array
     */
    public function indexProvider()
    {
        return [
            'account1 user1 (only account user orders)' => [
                'input' => [
                    'login' => LoadOrderUsers::ACCOUNT1_USER1,
                    'password' => LoadOrderUsers::ACCOUNT1_USER1,
                ],
                'expected' => [
                    'data' => [
                        [
                            'identifier' => LoadOrders::ORDER2,
                        ],
                    ],
                    'columns' => [
                        'id',
                        'identifier',
                        'createdAt',
                        'updatedAt',
                        'view_link',
                    ],
                ],
            ],
            'account1 user2 (all account orders)' => [
                'input' => [
                    'login' => LoadOrderUsers::ACCOUNT1_USER2,
                    'password' => LoadOrderUsers::ACCOUNT1_USER2,
                ],
                'expected' => [
                    'data' => [
                        [
                            'identifier' => LoadOrders::ORDER1,
                        ],
                        [
                            'identifier' => LoadOrders::ORDER2,
                        ],
                        [
                            'identifier' => LoadOrders::ORDER3,
                        ],
                        [
                            'identifier' => LoadOrders::ORDER4,
                        ],
                    ],
                    'columns' => [
                        'id',
                        'identifier',
                        'createdAt',
                        'updatedAt',
                        'view_link',
                    ],
                ],
            ],
            'account1 user3 (all account orders and accountUser column)' => [
                'input' => [
                    'login' => LoadOrderUsers::ACCOUNT1_USER3,
                    'password' => LoadOrderUsers::ACCOUNT1_USER3,
                ],
                'expected' => [
                    'data' => [
                        [
                            'identifier' => LoadOrders::ORDER1,
                        ],
                        [
                            'identifier' => LoadOrders::ORDER2,
                        ],
                        [
                            'identifier' => LoadOrders::ORDER3,
                        ],
                        [
                            'identifier' => LoadOrders::ORDER4,
                        ],
                    ],
                    'columns' => [
                        'id',
                        'identifier',
                        'accountUserName',
                        'createdAt',
                        'updatedAt',
                        'view_link',
                    ],
                ],
            ],
            'account2 user1 (only account user orders)' => [
                'input' => [
                    'login' => LoadOrderUsers::ACCOUNT2_USER1,
                    'password' => LoadOrderUsers::ACCOUNT2_USER1,
                ],
                'expected' => [
                    'data' => [
                        [
                            'identifier' => LoadOrders::ORDER6,
                        ],
                    ],
                    'columns' => [
                        'id',
                        'identifier',
                        'createdAt',
                        'updatedAt',
                        'view_link',
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
                    'identifier' => LoadOrders::ORDER2,
                    'login' => LoadOrderUsers::ACCOUNT1_USER1,
                    'password' => LoadOrderUsers::ACCOUNT1_USER1,
                ],
                'expected' => [
                    'columns' => [
                        [
                            'label' => 'orob2b.frontend.order.id.label',
                            'property' => 'id',
                        ],
                        [
                            'label' => 'orob2b.frontend.order.identifier.label',
                            'property' => 'identifier',
                        ],
                    ],
                ],
            ],
            'account1 user3 (AccountUser:VIEW_LOCAL)' => [
                'input' => [
                    'identifier' => LoadOrders::ORDER3,
                    'login' => LoadOrderUsers::ACCOUNT1_USER3,
                    'password' => LoadOrderUsers::ACCOUNT1_USER3,
                ],
                'expected' => [
                    'columns' => [
                        [
                            'label' => 'orob2b.frontend.order.id.label',
                            'property' => 'id',
                        ],
                        [
                            'label' => 'orob2b.frontend.order.identifier.label',
                            'property' => 'identifier',
                        ],
                        [
                            'label' => 'orob2b.frontend.order.account_user.label',
                            'property' => 'account_user',
                        ],
                    ],
                ],
            ],
        ];
    }
}
