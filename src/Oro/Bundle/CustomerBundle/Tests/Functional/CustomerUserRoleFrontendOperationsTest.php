<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional;

use Doctrine\Common\Persistence\ObjectRepository;
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
        $this->assertJsonResponseStatusCodeEquals($result, 403);

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
        if ($status === 200) {
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
                'status' => 403,
            ],
            'sibling user: LOCAL_VIEW_ONLY' => [
                'login' => LoadCustomerUserRoleACLData::USER_ACCOUNT_1_ROLE_LOCAL_VIEW_ONLY,
                'resource' => LoadCustomerUserRoleACLData::ROLE_WITH_ACCOUNT_1_USER_LOCAL,
                'status' => 403,
            ],
            'parent customer: LOCAL' => [
                'login' => LoadCustomerUserRoleACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'resource' => LoadCustomerUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL,
                'status' => 403,
            ],
            'parent customer: DEEP_VIEW_ONLY' => [
                'login' => LoadCustomerUserRoleACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY,
                'resource' => LoadCustomerUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL,
                'status' => 403,
            ],
            'different customer: DEEP' => [
                'login' => LoadCustomerUserRoleACLData::USER_ACCOUNT_2_ROLE_DEEP,
                'resource' => LoadCustomerUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL,
                'status' => 403,
            ],
            'same customer: LOCAL' => [
                'login' => LoadCustomerUserRoleACLData::USER_ACCOUNT_1_ROLE_LOCAL,
                'resource' => LoadCustomerUserRoleACLData::ROLE_WITH_ACCOUNT_1_USER_DEEP,
                'status' => 200,
            ],
            'parent customer: DEEP' => [
                'login' => LoadCustomerUserRoleACLData::USER_ACCOUNT_1_ROLE_DEEP,
                'resource' => LoadCustomerUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL,
                'status' => 200,
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
        $entityId = $customerUserRole->getId();
        $entityClass = 'Oro\Bundle\CustomerBundle\Entity\CustomerUserRole';
        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_frontend_action_operation_execute',
                [
                    'operationName' => $operationName,
                    'route' => 'oro_customer_frontend_customer_user_role_view',
                    'entityId' => $entityId,
                    'entityClass' => $entityClass
                ]
            ),
            $this->getOperationExecuteParams($operationName, $entityId, $entityClass),
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
    }

    /**
     * @param $operationName
     * @param $entityId
     * @param $entityClass
     * @param $datagrid
     *
     * @return array
     */
    protected function getOperationExecuteParams($operationName, $entityId, $entityClass, $datagrid = null)
    {
        $actionContext = [
            'entityId'    => $entityId,
            'entityClass' => $entityClass,
            'datagrid'    => $datagrid
        ];
        $container = self::getContainer();
        $operation = $container->get('oro_action.operation_registry')->findByName($operationName);
        $actionData = $container->get('oro_action.helper.context')->getActionData($actionContext);

        $tokenData = $container
            ->get('oro_action.operation.execution.form_provider')
            ->createTokenData($operation, $actionData);
        // this is done because of unclear behaviour symfony mocked token session storage
        // which do not save data before embedded request done and created data do not available in sub request
        // in the test environment
        $container->get('session')->save();

        return $tokenData;
    }
}
