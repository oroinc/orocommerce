<?php

namespace Oro\Bundle\CustomerBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;

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
            $anonymousGroupId = $this->configManager->get('oro_customer.anonymous_account_group');

            if ($anonymousGroupId) {
                return $this->doctrineHelper->getEntityReference(
                    'OroCustomerBundle:AccountGroup',
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
