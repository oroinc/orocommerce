<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional;

use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData;

/**
 * @dbIsolation
 */
class AccountUserActionsTest extends AbstractAccountUserActionsTestCase
{
    const EMAIL = LoadAccountUserData::EMAIL;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                'Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getAccountUserEnableOperationName()
    {
        return 'orob2b_account_accountuser_enable';
    }

    /**
     * {@inheritdoc}
     */
    protected function getAccountUserDisableOperationName()
    {
        return 'orob2b_account_accountuser_disable';
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
                    'route' => 'orob2b_account_account_user_view',
                    'entityId' => $accountUser->getId(),
                    'entityClass' => 'Oro\Bundle\AccountBundle\Entity\AccountUser'
                ]
            )
        );
    }
}
