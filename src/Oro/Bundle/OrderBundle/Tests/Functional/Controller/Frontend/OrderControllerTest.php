<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\Client;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrdersACLData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderUserACLData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class OrderControllerTest extends WebTestCase
{
    const ORDER_PO_NUMBER = 'PO-NUMBER';
    const QUICK_ADD_ORDER_PO_NUMBER = 'QUICK-ADD-PO-NUMBER';
    const ORDER_PO_NUMBER_UPDATED = 'PO-NUMBER-UP';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var DateTimeFormatterInterface
     */
    protected $dateFormatter;

    /**
     * @var NumberFormatter
     */
    protected $numberFormatter;

    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                LoadOrders::class,
                LoadOrdersACLData::class,
            ]
        );

        $this->dateFormatter = $this->getContainer()->get('oro_locale.formatter.date_time');
        $this->numberFormatter = $this->getContainer()->get('oro_locale.formatter.number');
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_order_frontend_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString('frontend-orders-grid', $crawler->html());
    }

    public function testOrdersGrid()
    {
        $response = $this->client->requestFrontendGrid('frontend-orders-grid');

        $result = static::getJsonResponseContent($response, 200);

        $myOrderData = [];
        foreach ($result['data'] as $row) {
            if ($row['identifier'] === LoadOrders::MY_ORDER) {
                $myOrderData = $row;
                break;
            }
        }

        $this->assertArrayHasKey('poNumber', $myOrderData);
        $this->assertEquals('PO_NUM', $myOrderData['poNumber']);
    }

    /**
     * @group frontend-ACL
     * @dataProvider gridACLProvider
     *
     * @param string $user
     * @param string $indexResponseStatus
     * @param string $gridResponseStatus
     * @param array $data
     */
    public function testOrdersGridACL($user, $indexResponseStatus, $gridResponseStatus, array $data = [])
    {
        $this->loginUser($user);
        $this->client->request('GET', $this->getUrl('oro_order_frontend_index'));
        $this->assertSame($indexResponseStatus, $this->client->getResponse()->getStatusCode());
        $response = $this->client->requestGrid(
            [
                'gridName' => 'frontend-orders-grid',
            ]
        );

        self::assertResponseStatusCodeEquals($response, $gridResponseStatus);
        if (200 === $gridResponseStatus) {
            $result = self::jsonToArray($response->getContent());
            $actual = array_column($result['data'], 'id');
            $actual = array_map('intval', $actual);
            $expected = array_map(
                function ($ref) {
                    return $this->getReference($ref)->getId();
                },
                $data
            );
            sort($expected);
            sort($actual);
            $this->assertEquals($expected, $actual);
        }
    }

    /**
     * @return array
     */
    public function gridACLProvider()
    {
        return [
            'NOT AUTHORISED' => [
                'user' => '',
                'indexResponseStatus' => 401,
                'gridResponseStatus' => 403,
                'data' => [],
            ],
            'BASIC: own orders' => [
                'user' => LoadOrderUserACLData::USER_ACCOUNT_1_ROLE_BASIC,
                'indexResponseStatus' => 200,
                'gridResponseStatus' => 200,
                'data' => [
                    LoadOrdersACLData::ORDER_ACC_1_USER_BASIC
                ],
            ],
            'LOCAL: all orders on customer level' => [
                'user' => LoadOrderUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'indexResponseStatus' => 200,
                'gridResponseStatus' => 200,
                'data' => [
                    LoadOrdersACLData::ORDER_ACC_1_USER_BASIC,
                    LoadOrdersACLData::ORDER_ACC_1_USER_DEEP,
                    LoadOrdersACLData::ORDER_ACC_1_USER_LOCAL,
                ],
            ],
            'DEEP: all orders on customer level and child customers' => [
                'user' => LoadOrderUserACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'indexResponseStatus' => 200,
                'gridResponseStatus' => 200,
                'data' => [
                    LoadOrdersACLData::ORDER_ACC_1_USER_BASIC,
                    LoadOrdersACLData::ORDER_ACC_1_USER_DEEP,
                    LoadOrdersACLData::ORDER_ACC_1_USER_LOCAL,
                    LoadOrdersACLData::ORDER_ACC_1_1_USER_LOCAL,
                ],
            ],
        ];
    }

    /**
     * @dataProvider testViewDataProvider
     *
     * @param string $resource
     * @param string $user
     * @param int $status
     */
    public function testView($resource, $user, $status)
    {
        $this->loginUser($user);

        /** @var Order $order */
        $order = $this->getReference($resource);
        $crawler = $this->client->request('GET', $this->getUrl('oro_order_frontend_view', ['id' => $order->getId()]));

        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, $status);

        if (200 === $status) {
            $this->assertViewPage($crawler, ['Notes']);
        }
    }

    /**
     * @return array
     */
    public function testViewDataProvider()
    {
        return [
            'anonymous user' => [
                'resource' => LoadOrdersACLData::ORDER_ACC_1_USER_BASIC,
                'user' => '',
                'status' => 401,
            ],
            'user from another customer' => [
                'resource' => LoadOrdersACLData::ORDER_ACC_1_USER_BASIC,
                'user' => LoadOrderUserACLData::USER_ACCOUNT_2_ROLE_LOCAL,
                'status' => 403,
            ],
            'user from parent customer : LOCAL' => [
                'resource' => LoadOrdersACLData::ORDER_ACC_1_1_USER_LOCAL,
                'user' => LoadOrderUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'status' => 403,
            ],
            'user from same customer : BASIC' => [
                'resource' => LoadOrdersACLData::ORDER_ACC_1_USER_LOCAL,
                'user' => LoadOrderUserACLData::USER_ACCOUNT_1_ROLE_BASIC,
                'status' => 403,
            ],
            'user from same customer : LOCAL' => [
                'resource' => LoadOrdersACLData::ORDER_ACC_1_USER_BASIC,
                'user' => LoadOrderUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'status' => 200,
            ],
            'user from parent customer : DEEP' => [
                'resource' => LoadOrdersACLData::ORDER_ACC_1_1_USER_LOCAL,
                'user' => LoadOrderUserACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'status' => 200,
            ],
            'resource owner' => [
                'resource' => LoadOrdersACLData::ORDER_ACC_1_USER_BASIC,
                'user' => LoadOrderUserACLData::USER_ACCOUNT_1_ROLE_BASIC,
                'status' => 200,
            ],
        ];
    }

    public function assertViewPage(Crawler $crawler, array $expectedViewData)
    {
        $html = $crawler->html();
        foreach ($expectedViewData as $data) {
            static::assertStringContainsString($data, $html);
        }
    }
}
