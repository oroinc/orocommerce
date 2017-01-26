<?php

namespace Oro\Bundle\CustomerBundle\Provider;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ScopeBundle\Manager\AbstractScopeCriteriaProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ScopeCustomerGroupCriteriaProvider extends AbstractScopeCriteriaProvider
{
    const FIELD_NAME = 'customerGroup';

    /**
     * @var SecurityFacade
     */
    protected $tokenStorage;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @var CustomerUserRelationsProvider
     */
    protected $customerUserProvider;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param CustomerUserRelationsProvider $customerUserRelationsProvider
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        CustomerUserRelationsProvider $customerUserRelationsProvider
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->customerUserProvider = $customerUserRelationsProvider;
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
        if (!($loggedUser instanceof CustomerUser)) {
            $loggedUser = null;
        }

        return [$this->getCriteriaField() => $this->customerUserProvider->getCustomerGroup($loggedUser)];
    }

    /**
     * {@inheritdoc}
     */
    public function getCriteriaValueType()
    {
        return CustomerGroup::class;
    }
}
