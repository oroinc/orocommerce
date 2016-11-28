<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Controller\Frontend\Api\Rest;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserACLData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class AccountUserControllerTest extends WebTestCase
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
                LoadAccountUserACLData::class,
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
                'oro_api_account_frontend_delete_account_user',
                ['id' => $this->getReference($resource)->getId()]
            )
        );
        $this->assertResponseStatusCodeEquals($this->client->getResponse(), $status);

        if ($status === 204) {
            /** @var \Oro\Bundle\CustomerBundle\Entity\AccountUser $user */
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
                'resource' => LoadAccountUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'status' => 401,
            ],
            'same account: LOCAL_VIEW_ONLY' => [
                'login' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_LOCAL_VIEW_ONLY,
                'resource' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'status' => 403,
            ],
            'parent account: LOCAL' => [
                'login' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'resource' => LoadAccountUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'status' => 403,
            ],
            'parent account: DEEP_VIEW_ONLY' => [
                'login' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY,
                'resource' => LoadAccountUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'status' => 403,
            ],
            'parent account: DEEP' => [
                'login' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'resource' => LoadAccountUserACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
                'status' => 204,
            ],
            'same account: LOCAL' => [
                'login' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'resource' => LoadAccountUserACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY,
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
        return $this->getObjectManager()->getRepository('OroCustomerBundle:AccountUser');
    }
}
