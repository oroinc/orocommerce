<?php

namespace OroB2B\Bundle\AccountBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class AccountUserRelationsProvider
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param ConfigManager $configManager
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(ConfigManager $configManager, DoctrineHelper $doctrineHelper)
    {
        $this->configManager = $configManager;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param AccountUser|null $accountUser
     * @return null|Account
     */
    public function getAccount(AccountUser $accountUser = null)
    {
        if ($accountUser) {
            return $accountUser->getAccount();
        }

        return null;
    }

    /**
     * @param AccountUser|null $accountUser
     * @return null|AccountGroup
     */
    public function getAccountGroup(AccountUser $accountUser = null)
    {
        if ($accountUser) {
            $account = $this->getAccount($accountUser);
            if ($account) {
                return $account->getGroup();
            }
        } else {
            $anonymousGroupId = $this->configManager->get('oro_b2b_account.anonymous_account_group');

            if ($anonymousGroupId) {
                return $this->doctrineHelper->getEntityReference(
                    'OroB2BAccountBundle:AccountGroup',
                    $anonymousGroupId
                );
            }
        }

        return null;
    }

    /**
     * @param AccountUser|null $accountUser
     * @return null|Account
     */
    public function getAccountIncludingEmpty(AccountUser $accountUser = null)
    {
        $account = $this->getAccount($accountUser);
        if (!$account) {
            $account = new Account();
            $account->setGroup($this->getAccountGroup($accountUser));
        }
        
        return $account;
    }
}
