<?php

namespace Oro\Bundle\CustomerBundle\Provider;

use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\ScopeBundle\Manager\AbstractScopeCriteriaProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ScopeAccountGroupCriteriaProvider extends AbstractScopeCriteriaProvider
{
    const FIELD_NAME = 'accountGroup';

    /**
     * @var SecurityFacade
     */
    protected $tokenStorage;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @var AccountUserRelationsProvider
     */
    protected $accountUserProvider;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param AccountUserRelationsProvider $accountUserRelationsProvider
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        AccountUserRelationsProvider $accountUserRelationsProvider
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->accountUserProvider = $accountUserRelationsProvider;
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
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return [];
        }
        $loggedUser = $token->getUser();
        if (null === $loggedUser || $loggedUser instanceof AccountUser) {
            return [$this->getCriteriaField() => $this->accountUserProvider->getAccountGroup($loggedUser)];
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
