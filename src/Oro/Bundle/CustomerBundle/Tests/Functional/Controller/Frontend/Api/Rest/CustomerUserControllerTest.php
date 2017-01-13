<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Controller\Frontend\Api\Rest;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserACLData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class CustomerUserControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                LoadCustomerUserACLData::class,
            ]
        );
    }

    /**
     * @dataProvider deleteDataProvider
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
                'oro_api_customer_frontend_delete_customer_user',
                ['id' => $this->getReference($resource)->getId()]
            )
        );
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), $status);

        if ($status === 204) {
            /** @var \Oro\Bundle\CustomerBundle\Entity\CustomerUser $user */
            $user = $this->getUserRepository()->findOneBy(['email' => $resource]);
            $this->assertNull($user);
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
                'resource' => LoadCustomerUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'status' => 401,
            ],
            'same customer: LOCAL_VIEW_ONLY' => [
                'login' => LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_LOCAL_VIEW_ONLY,
                'resource' => LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'status' => 403,
            ],
            'parent customer: LOCAL' => [
                'login' => LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'resource' => LoadCustomerUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'status' => 403,
            ],
            'parent customer: DEEP_VIEW_ONLY' => [
                'login' => LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY,
                'resource' => LoadCustomerUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'status' => 403,
            ],
            'parent customer: DEEP' => [
                'login' => LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'resource' => LoadCustomerUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'status' => 204,
            ],
            'same customer: LOCAL' => [
                'login' => LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'resource' => LoadCustomerUserACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY,
                'status' => 204,
            ],
        ];
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @return ObjectRepository
     */
    protected function getUserRepository()
    {
        return $this->getObjectManager()->getRepository('OroCustomerBundle:CustomerUser');
    }
}
