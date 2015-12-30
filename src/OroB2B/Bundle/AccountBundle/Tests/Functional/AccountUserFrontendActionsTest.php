<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional;

use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

/**
 * @dbIsolation
 */
class AccountUserFrontendActionsTest extends AbstractAccountUserActionsTest
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
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getAccountUserEnableActionName()
    {
        return 'orob2b_account_frontend_accountuser_enable_action';
    }

    /**
     * {@inheritdoc}
     */
    protected function getAccountUserDisableActionName()
    {
        return 'orob2b_account_frontend_accountuser_disable_action';
    }

    /**
     * @param AccountUser $accountUser
     * @param string $actionName
     */
    protected function executeAction(AccountUser $accountUser, $actionName)
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_api_frontend_action_execute_actions',
                [
                    'actionName' => $actionName,
                    'route' => 'orob2b_account_frontend_account_user_view',
                    'entityId' => $accountUser->getId(),
                    'entityClass' => 'OroB2B\Bundle\AccountBundle\Entity\AccountUser'
                ]
            )
        );
    }
}
