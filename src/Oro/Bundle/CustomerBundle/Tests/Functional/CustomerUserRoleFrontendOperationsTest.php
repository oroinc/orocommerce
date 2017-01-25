<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional;

use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserRoleACLData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class CustomerUserRoleFrontendOperationsTest extends WebTestCase
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
                LoadCustomerUserRoleACLData::class
            ]
        );
    }

    public function testDeletePredefinedRole()
    {
        $this->loginUser(LoadCustomerUserRoleACLData::USER_ACCOUNT_1_ROLE_LOCAL);
        $predefinedRole = $this->getReference(LoadCustomerUserRoleACLData::ROLE_WITHOUT_ACCOUNT_1_USER_LOCAL);
        $this->assertNotNull($predefinedRole);

        $this->executeOperation($predefinedRole, 'oro_customer_frontend_delete_role');

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, Response::HTTP_FORBIDDEN);

        $this->assertNotNull($this->getReference(LoadCustomerUserRoleACLData::ROLE_WITHOUT_ACCOUNT_1_USER_LOCAL));
    }

    /**
     * @dataProvider deleteDataProvider
     *
     * @param string $login
     * @param string $resource
     * @param int $status
     */
    public function testDeleteCustomizedRole($login, $resource, $status)
    {
        $this->loginUser($login);
        /** @var CustomerUserRole $customizedRole */
        $customizedRole = $this->getReference($resource);
        $this->assertNotNull($customizedRole);

        $this->executeOperation($customizedRole, 'oro_customer_frontend_delete_role');

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, $status);
        if ($status === Response::HTTP_OK) {
            /** @var CustomerUserRole $role */
            $role = $this->getRepository()->findOneBy(['label' => $resource]);
            $this->assertNull($role);
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
                'resource' => LoadCustomerUserRoleACLData::ROLE_WITH_ACCOUNT_1_USER_LOCAL,
                'status' => Response::HTTP_FORBIDDEN,
            ],
            'sibling user: LOCAL_VIEW_ONLY' => [
                'login' => LoadCustomerUserRoleACLData::USER_ACCOUNT_1_ROLE_LOCAL_VIEW_ONLY,
                'resource' => LoadCustomerUserRoleACLData::ROLE_WITH_ACCOUNT_1_USER_LOCAL,
                'status' => Response::HTTP_FORBIDDEN,
            ],
            'parent customer: LOCAL' => [
                'login' => LoadCustomerUserRoleACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'resource' => LoadCustomerUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL,
                'status' => Response::HTTP_FORBIDDEN,
            ],
            'parent customer: DEEP_VIEW_ONLY' => [
                'login' => LoadCustomerUserRoleACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY,
                'resource' => LoadCustomerUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL,
                'status' => Response::HTTP_FORBIDDEN,
            ],
            'different customer: DEEP' => [
                'login' => LoadCustomerUserRoleACLData::USER_ACCOUNT_2_ROLE_DEEP,
                'resource' => LoadCustomerUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL,
                'status' => Response::HTTP_FORBIDDEN,
            ],
            'same customer: LOCAL' => [
                'login' => LoadCustomerUserRoleACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'resource' => LoadCustomerUserRoleACLData::ROLE_WITH_ACCOUNT_1_USER_DEEP,
                'status' => Response::HTTP_OK,
            ],
            'parent customer: DEEP' => [
                'login' => LoadCustomerUserRoleACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'resource' => LoadCustomerUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL,
                'status' => Response::HTTP_OK,
            ],
        ];
    }

    /**
     * @return ObjectRepository
     */
    private function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(CustomerUserRole::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function executeOperation(CustomerUserRole $customerUserRole, $operationName)
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_frontend_action_operation_execute',
                [
                    'operationName' => $operationName,
                    'route' => 'oro_customer_frontend_customer_user_role_view',
                    'entityId' => $customerUserRole->getId(),
                    'entityClass' => 'Oro\Bundle\CustomerBundle\Entity\CustomerUserRole'
                ]
            ),
            [],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
    }
}
