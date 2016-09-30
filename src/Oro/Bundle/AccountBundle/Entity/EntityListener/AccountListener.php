<?php

namespace Oro\Bundle\AccountBundle\Entity\EntityListener;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\WebsiteSearchBundle\Driver\AccountPartialUpdateDriverInterface;

class AccountListener
{
    /**
     * @var AccountPartialUpdateDriverInterface
     */
    protected $partialUpdateDriver;
    
    /**
     * @param AccountPartialUpdateDriverInterface $partialUpdateDriver
     */
    public function __construct(AccountPartialUpdateDriverInterface $partialUpdateDriver)
    {
        $this->partialUpdateDriver = $partialUpdateDriver;
    }

    /**
     * @param Account $account
     */
    public function postPersist(Account $account)
    {
        $this->partialUpdateDriver->createAccountWithoutAccountGroupVisibility($account);
    }

    /**
     * @param Account $account
     */
    public function preRemove(Account $account)
    {
        $this->partialUpdateDriver->deleteAccountVisibility($account);
    }
}
