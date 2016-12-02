<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Controller\Frontend\Api\Rest;

use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserACLData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrdersACLData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class OrderControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                LoadOrdersACLData::class,
            ]
        );
    }

    /**
     * @dataProvider deleteDataProvider
     *
     * @param string $login
     * @param string $resource
     * @param int $status
     */
    public function testDelete($login, $resource, $status)
    {
        $this->loginUser($login);
        $this->client->request(
            'DELETE',
            $this->getUrl(
                'oro_api_frontend_delete_order',
                ['id' => $this->getReference($resource)->getId()]
            )
        );
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), $status);

        if ($status === 204) {
            /** @var Order $order */
            $order = $this->getRepository()->findOneBy(['identifier' => $resource]);
            $this->assertNull($order);
        }
    }

    /**
     * @return array
     */
    public function deleteDataProvider()
    {
        return [
            'anonymous user' => [
                'login' => '',
                'resource' => LoadOrdersACLData::ORDER_ACC_1_USER_LOCAL,
                'status' => 401,
            ],
            'sibling user: BASIC' => [
                'login' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_BASIC,
                'resource' => LoadOrdersACLData::ORDER_ACC_1_USER_LOCAL,
                'status' => 403,
            ],
            'sibling user: LOCAL_VIEW_ONLY' => [
                'login' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_LOCAL_VIEW_ONLY,
                'resource' => LoadOrdersACLData::ORDER_ACC_1_USER_LOCAL,
                'status' => 403,
            ],
            'parent account: LOCAL' => [
                'login' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'resource' => LoadOrdersACLData::ORDER_ACC_1_1_USER_LOCAL,
                'status' => 403,
            ],
            'parent account: DEEP_VIEW_ONLY' => [
                'login' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY,
                'resource' => LoadOrdersACLData::ORDER_ACC_1_1_USER_LOCAL,
                'status' => 403,
            ],
            'different account: DEEP' => [
                'login' => LoadAccountUserACLData::USER_ACCOUNT_2_ROLE_DEEP,
                'resource' => LoadOrdersACLData::ORDER_ACC_1_1_USER_LOCAL,
                'status' => 403,
            ],
            'owner: BASIC' => [
                'login' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'resource' => LoadOrdersACLData::ORDER_ACC_1_USER_BASIC,
                'status' => 204,
            ],
            'same account: LOCAL' => [
                'login' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'resource' => LoadOrdersACLData::ORDER_ACC_1_USER_DEEP,
                'status' => 204,
            ],
            'parent account: DEEP' => [
                'login' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'resource' => LoadOrdersACLData::ORDER_ACC_1_1_USER_LOCAL,
                'status' => 204,
            ],
        ];
    }

    /**
     * @return ObjectRepository
     */
    private function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(Order::class);
    }
}
