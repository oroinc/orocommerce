<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCheckoutACLData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCheckoutUserACLData;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrdersACLData;

/**
 * @group=segfault
 */
class CheckoutControllerAclTest extends FrontendWebTestCase
{
    use ConfigManagerAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient();
        $this->setCurrentWebsite('default');
        $this->loadFixtures([LoadOrdersACLData::class, LoadCheckoutACLData::class]);
    }

    /**
     * @group frontend-ACL
     * @dataProvider gridAclProvider
     */
    public function testOrdersGridAcl(
        string $user,
        int $indexResponseStatus,
        int $gridResponseStatus,
        array $data = []
    ) {
        $this->initClient([], self::generateBasicAuthHeader($user, $user));

        $configManager = self::getConfigManager();
        $configManager->set('oro_checkout.frontend_open_orders_separate_page', true);
        $configManager->flush();

        $this->client->request('GET', $this->getUrl('oro_order_frontend_index'));
        $this->assertSame($indexResponseStatus, $this->client->getResponse()->getStatusCode());
        $response = $this->client->requestGrid(
            [
                'gridName' => 'frontend-checkouts-grid',
            ],
            [],
            true,
            'oro_frontend_datagrid_index'
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

    public function gridAclProvider(): array
    {
        return [
            'NOT AUTHORISED' => [
                'user' => '',
                'indexResponseStatus' => 401,
                'gridResponseStatus' => 401,
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
     * @dataProvider viewDataProvider
     */
    public function testView(string $resource, string $user, int $status)
    {
        $this->initClient([], $this->generateBasicAuthHeader($user, $user));
        /** @var Checkout $checkout */
        $checkout = $this->getReference($resource);
        $this->client->request('GET', $this->getUrl('oro_checkout_frontend_checkout', ['id' => $checkout->getId()]));

        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, $status);
    }

    public function viewDataProvider(): array
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
