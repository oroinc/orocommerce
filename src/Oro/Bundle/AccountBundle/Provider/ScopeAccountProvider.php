<?php

namespace Oro\Bundle\AccountBundle\Provider;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\ScopeBundle\Manager\AbstractScopeProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class ScopeAccountProvider extends AbstractScopeProvider
{
    const ACCOUNT = 'account';

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @return array
     */
    public function getCriteriaForCurrentScope()
    {
        $loggedUser = $this->securityFacade->getLoggedUser();
        if (null !== $loggedUser && $loggedUser instanceof AccountUser) {
            return [self::ACCOUNT => $loggedUser->getAccount()];
        }

        return [];
    }

    /**
     * @return string
     */
    protected function getCriteriaField()
    {
        return static::ACCOUNT;
    }

    /**
     * @return string
     */
    protected function getCriteriaValueType()
    {
        return Account::class;
    }
}
