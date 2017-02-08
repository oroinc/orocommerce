<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCheckoutACLData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCheckoutUserACLData;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrdersACLData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CheckoutControllerAclTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures(
            [
                LoadOrdersACLData::class,
                LoadCheckoutACLData::class,
            ]
        );
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
        $configManager = $this
            ->getContainer()
            ->get('oro_config.manager');

        $configManager->set('oro_checkout.frontend_open_orders_separate_page', true);
        $configManager->flush();

        $this->loginUser($user);

        $this->client->request('GET', $this->getUrl('oro_order_frontend_index'));
        $this->assertSame($indexResponseStatus, $this->client->getResponse()->getStatusCode());
        $response = $this->client->requestGrid(
            [
                'gridName' => 'frontend-checkouts-grid',
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
                'user' => LoadCheckoutUserACLData::USER_ACCOUNT_1_ROLE_BASIC,
                'indexResponseStatus' => 200,
                'gridResponseStatus' => 200,
                'data' => [
                    LoadCheckoutACLData::CHECKOUT_ACC_1_USER_BASIC
                ],
            ],
            'LOCAL: all orders on customer level' => [
                'user' => LoadCheckoutUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'indexResponseStatus' => 200,
                'gridResponseStatus' => 200,
                'data' => [
                    LoadCheckoutACLData::CHECKOUT_ACC_1_USER_BASIC,
                    LoadCheckoutACLData::CHECKOUT_ACC_1_USER_DEEP,
                    LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL,
                ],
            ],
            'DEEP: all orders on customer level and child customers' => [
                'user' => LoadCheckoutUserACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'indexResponseStatus' => 200,
                'gridResponseStatus' => 200,
                'data' => [
                    LoadCheckoutACLData::CHECKOUT_ACC_1_USER_BASIC,
                    LoadCheckoutACLData::CHECKOUT_ACC_1_USER_DEEP,
                    LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL,
                    LoadCheckoutACLData::CHECKOUT_ACC_1_1_USER_LOCAL,
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

        /** @var Checkout $checkout */
        $checkout = $this->getReference($resource);
        $this->client->request('GET', $this->getUrl('oro_checkout_frontend_checkout', ['id' => $checkout->getId()]));

        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, $status);
    }

    /**
     * @return array
     */
    public function testViewDataProvider()
    {
        return [
            'anonymous user' => [
                'resource' => LoadCheckoutACLData::CHECKOUT_ACC_1_USER_BASIC,
                'user' => '',
                'status' => 401,
            ],
            'user from another customer' => [
                'resource' => LoadCheckoutACLData::CHECKOUT_ACC_1_USER_BASIC,
                'user' => LoadCheckoutUserACLData::USER_ACCOUNT_2_ROLE_LOCAL,
                'status' => 403,
            ],
            'user from parent customer : LOCAL' => [
                'resource' => LoadCheckoutACLData::CHECKOUT_ACC_1_1_USER_LOCAL,
                'user' => LoadCheckoutUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'status' => 403,
            ],
            'user from same customer : BASIC' => [
                'resource' => LoadCheckoutACLData::CHECKOUT_ACC_1_USER_LOCAL,
                'user' => LoadCheckoutUserACLData::USER_ACCOUNT_1_ROLE_BASIC,
                'status' => 403,
            ],
            'user from same customer : LOCAL' => [
                'resource' => LoadCheckoutACLData::CHECKOUT_ACC_1_USER_BASIC,
                'user' => LoadCheckoutUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'status' => 200,
            ],
            'user from parent customer : DEEP' => [
                'resource' => LoadCheckoutACLData::CHECKOUT_ACC_1_1_USER_LOCAL,
                'user' => LoadCheckoutUserACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'status' => 200,
            ],
            'resource owner' => [
                'resource' => LoadCheckoutACLData::CHECKOUT_ACC_1_USER_BASIC,
                'user' => LoadCheckoutUserACLData::USER_ACCOUNT_1_ROLE_BASIC,
                'status' => 200,
            ],
        ];
    }
}
