<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;

/**
 * @dbIsolation
 */
class AccountUserFrontendActionsTest extends AbstractAccountUserActionsTestCase
{
    const EMAIL = 'account.user2@test.com';

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getAccountUserEnableOperationName()
    {
        return 'oro_account_frontend_accountuser_enable';
    }

    /**
     * {@inheritdoc}
     */
    protected function getAccountUserDisableOperationName()
    {
        return 'oro_account_frontend_accountuser_disable';
    }

    /**
     * {@inheritdoc}
     */
    protected function executeOperation(AccountUser $accountUser, $operationName)
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_frontend_action_operation_execute',
                [
                    'operationName' => $operationName,
                    'route' => 'oro_account_frontend_account_user_view',
                    'entityId' => $accountUser->getId(),
                    'entityClass' => 'Oro\Bundle\CustomerBundle\Entity\AccountUser'
                ]
            ),
            [],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
    }
}
