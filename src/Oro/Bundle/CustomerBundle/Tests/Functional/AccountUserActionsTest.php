<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserData;

/**
 * @dbIsolation
 */
class AccountUserActionsTest extends AbstractAccountUserActionsTestCase
{
    const EMAIL = LoadAccountUserData::EMAIL;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
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
        return 'oro_account_accountuser_enable';
    }

    /**
     * {@inheritdoc}
     */
    protected function getAccountUserDisableOperationName()
    {
        return 'oro_account_accountuser_disable';
    }

    /**
     * {@inheritdoc}
     */
    protected function executeOperation(AccountUser $accountUser, $operationName)
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => $operationName,
                    'route' => 'oro_customer_account_user_view',
                    'entityId' => $accountUser->getId(),
                    'entityClass' => 'Oro\Bundle\CustomerBundle\Entity\AccountUser'
                ]
            )
        );
    }
}
