<?php

namespace Oro\Bundle\AccountBundle\Provider;

use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\ScopeBundle\Manager\AbstractScopeCriteriaProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\PropertyAccess\PropertyAccessor;

class ScopeAccountGroupCriteriaProvider extends AbstractScopeCriteriaProvider
{
    const FIELD_NAME = 'accountGroup';

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @return string
     */
    public function getCriteriaField()
    {
        return self::FIELD_NAME;
    }

    /**
     * @return array
     */
    public function getCriteriaForCurrentScope()
    {
        $loggedUser = $this->securityFacade->getLoggedUser();
        if (null !== $loggedUser
            && $loggedUser instanceof AccountUser
            && null !== $loggedUser->getAccount()
        ) {
            return [$this->getCriteriaField() => $loggedUser->getAccount()->getGroup()];
        }

        return [];
    }

    /**
     * @return string
     */
    protected function getCriteriaValueType()
    {
        return AccountGroup::class;
    }
}
